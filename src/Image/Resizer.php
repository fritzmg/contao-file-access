<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\Image;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FilesModel;
use Contao\Folder;
use Contao\Image\DeferredImage;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\PathUtil\Path;

class Resizer implements DeferredResizerInterface
{
    private $inner;
    private $protectedResizer;
    private $requestStack;
    private $scopeMatcher;
    private $projectDir;
    private $uploadPath;
    private $backendRoutePrefix;

    public function __construct(
        DeferredResizerInterface $inner,
        DeferredResizerInterface $protectedResizer,
        RequestStack $requestStack,
        ScopeMatcher $scopeMatcher,
        string $projectDir,
        string $uploadPath,
        string $backendRoutePrefix
    ) {
        $this->inner = $inner;
        $this->protectedResizer = $protectedResizer;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->projectDir = $projectDir;
        $this->uploadPath = $uploadPath;
        $this->backendRoutePrefix = $backendRoutePrefix;
    }

    public function getDeferredImage(string $targetPath, ImagineInterface $imagine): ?DeferredImageInterface
    {
        return $this->inner->getDeferredImage($targetPath, $imagine);
    }

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ?ImageInterface
    {
        return $this->inner->resizeDeferredImage($image, $blocking);
    }

    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $relImageDir = Path::makeRelative(\dirname($image->getPath()), $this->projectDir);

        if ($this->isResizedImagesProtected($relImageDir)) {
            $resized = $this->protectedResizer->resize($image, $config, $options);
            $request = $this->requestStack->getCurrentRequest();

            // Modify the image path for the back end
            if ($request && $this->scopeMatcher->isBackendRequest($request)) {
                $backendPath = Path::join($this->projectDir, $this->backendRoutePrefix, Path::makeRelative($resized->getPath(), $this->projectDir));

                return new DeferredImage(
                    $backendPath,
                    $resized->getImagine(),
                    $resized->getDimensions()
                );
            }

            return $resized;
        }

        return $this->inner->resize($image, $config, $options);
    }

    private function isResizedImagesProtected(string $relImageDir): bool
    {
        if (!Path::isBasePath($this->uploadPath, $relImageDir) || (new Folder($relImageDir))->isUnprotected()) {
            return false;
        }

        $filesModel = FilesModel::findByPath($relImageDir);

        if (null === $filesModel) {
            return false;
        }

        return (bool) $filesModel->protectResizedImages;
    }
}
