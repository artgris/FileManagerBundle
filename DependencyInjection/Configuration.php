<?php

namespace Artgris\Bundle\FileManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('artgris_file_manager');

        $rootNode
            ->children()
                ->arrayNode('conf')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('dir')->end()
                            ->scalarNode('theme')->end()
                            ->scalarNode('th')->end()
                            ->scalarNode('type')->end()
                            ->scalarNode('regex')->end()
                            ->scalarNode('service')->end()
                            ->scalarNode('accept')->end()
                            ->arrayNode('upload')
                                ->children()
                                    ->scalarNode('min_file_size')->end()
                                    ->scalarNode('max_file_size')->end()
                                    ->scalarNode('max_width')->end()
                                    ->scalarNode('max_height')->end()
                                    ->scalarNode('min_width')->end()
                                    ->scalarNode('min_height')->end()
                                    ->scalarNode('image_library')->end()
                                    ->arrayNode('image_versions')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('auto_orient')->end()
                                                ->scalarNode('crop')->end()
                                                ->scalarNode('max_width')->end()
                                                ->scalarNode('max_height')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
