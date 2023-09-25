<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\Routing;

use InspiredMinds\ContaoFileAccessBundle\Controller\ProtectedImagesController;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ProtectedImagesLoader extends Loader
{
    private string $pathPrefix;

    public function __construct(string $projectDir, string $imageTargetDir)
    {
        $this->pathPrefix = Path::makeRelative($imageTargetDir, $projectDir);
    }

    public function load($resource, string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $routes->add('contao_file_access_protected_images', new Route(
            '/'.$this->pathPrefix.'/{path}',
            [
                '_controller' => ProtectedImagesController::class,
                '_scope' => 'frontend',
            ],
            ['path' => '.+']
        ));

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'protected_images' === $type;
    }
}
