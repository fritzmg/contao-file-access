<?php

declare(strict_types=1);

/*
 * This file is part of the Contao File Access extension.
 *
 * (c) INSPIRED MINDS
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle;

use InspiredMinds\ContaoFileAccessBundle\DependencyInjection\Compiler\AdjustProtectedResizerServicePass;
use InspiredMinds\ContaoFileAccessBundle\DependencyInjection\Compiler\AdjustResizerServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoFileAccessBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AdjustProtectedResizerServicePass());
        $container->addCompilerPass(new AdjustResizerServicePass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
