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

use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\Resizer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AdjustProtectedResizerServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao_file_access.image.protected_resizer')) {
            return;
        }

        $definition = $container->getDefinition('contao_file_access.image.protected_resizer');

        if (class_exists(LegacyResizer::class)) {
            $definition->setClass(LegacyResizer::class);
        } else {
            $definition->setClass(Resizer::class);
        }
    }
}
