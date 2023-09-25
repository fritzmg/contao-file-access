<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoFileAccessBundle.
 *
 * (c) inspiredminds
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
}
