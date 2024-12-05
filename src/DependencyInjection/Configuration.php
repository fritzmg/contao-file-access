<?php

declare(strict_types=1);

/*
 * This file is part of the Contao File Access extension.
 *
 * (c) INSPIRED MINDS
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFileAccessBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Path;

class Configuration implements ConfigurationInterface
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao_file_access');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->booleanNode('protect_resized_images')
                    ->info('Also protects resized images of non-public folders which would otherwise be publicly available under assets/images.')
                    ->defaultFalse()
                ->end()
                ->scalarNode('protected_images_cache')
                    ->info('The folder in which resized images of protected files are stored.')
                    ->example('%kernel.project_dir%/protected_images')
                    ->cannotBeEmpty()
                    ->defaultValue(Path::join($this->projectDir, 'protected_images'))
                    ->validate()
                        ->always(static fn (string $value): string => Path::canonicalize($value))
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
