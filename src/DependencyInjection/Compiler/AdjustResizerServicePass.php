<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Disables the resizer if contao_file_access.protect_resized_images is not enabled.
 */
class AdjustResizerServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $protectResizedImages = $container->getParameter('contao_file_access.protect_resized_images');

        if ($protectResizedImages) {
            return;
        }

        if ($container->has('contao_file_access.image.resizer')) {
            $container->removeDefinition('contao_file_access.image.resizer');
        }

        if ($container->has('contao_file_access.image.legacy_resizer')) {
            $container->removeDefinition('contao_file_access.image.legacy_resizer');
        }
    }
}
