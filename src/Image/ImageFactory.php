<?php

namespace InspiredMinds\ContaoFileAccessBundle\Image;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\Image\ImageInterface;

class ImageFactory implements ImageFactoryInterface
{
    private $inner;

    public function __construct(ImageFactoryInterface $inner)
    {
        $this->inner = $inner;
    }

    public function create($path, $size = null, $options = null)
    {
        return $this->inner->create($path, $size, $options);
    }

    public function getImportantPartFromLegacyMode(ImageInterface $image, $mode)
    {
        return $this->inner->getImportantPartFromLegacyMode($image, $mode);
    }
}
