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

use Contao\Folder;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Path;

class Resizer implements DeferredResizerInterface
{
    public function __construct(
        private readonly DeferredResizerInterface $inner,
        private readonly DeferredResizerInterface $protectedResizer,
        private readonly string $projectDir,
        private readonly string $uploadPath,
        private readonly string $protectedCacheDir,
    ) {
    }

    public function getDeferredImage(string $targetPath, ImagineInterface $imagine): DeferredImageInterface|null
    {
        if (Path::isBasePath($this->protectedCacheDir, $targetPath)) {
            return $this->protectedResizer->getDeferredImage($targetPath, $imagine);
        }

        return $this->inner->getDeferredImage($targetPath, $imagine);
    }

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ImageInterface|null
    {
        if (Path::isBasePath($this->protectedCacheDir, $image->getPath())) {
            return $this->protectedResizer->resizeDeferredImage($image, $blocking);
        }

        return $this->inner->resizeDeferredImage($image, $blocking);
    }

    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $relImageDir = Path::makeRelative(\dirname($image->getPath()), $this->projectDir);

        if ($this->isResizedImagesProtected($relImageDir)) {
            return $this->protectedResizer->resize($image, $config, $options);
        }

        return $this->inner->resize($image, $config, $options);
    }

    private function isResizedImagesProtected(string $relImageDir): bool
    {
        return Path::isBasePath($this->uploadPath, $relImageDir) && !(new Folder($relImageDir))->isUnprotected();
    }
}
