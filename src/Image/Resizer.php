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
use Webmozart\PathUtil\Path;

class Resizer implements DeferredResizerInterface
{
    private $inner;
    private $protectedResizer;
    private $projectDir;
    private $uploadPath;

    public function __construct(
        DeferredResizerInterface $inner,
        DeferredResizerInterface $protectedResizer,
        string $projectDir,
        string $uploadPath
    ) {
        $this->inner = $inner;
        $this->protectedResizer = $protectedResizer;
        $this->projectDir = $projectDir;
        $this->uploadPath = $uploadPath;
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

        if (Path::isBasePath($this->uploadPath, $relImageDir) && !(new Folder($relImageDir))->isUnprotected()) {
            return $this->protectedResizer->resize($image, $config, $options);
        }

        return $this->inner->resize($image, $config, $options);
    }
}
