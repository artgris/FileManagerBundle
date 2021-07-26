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
        // symfony > 4.2
        $treeBuilder = new TreeBuilder('artgris_file_manager');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('web_dir')
                    ->defaultValue('public')
                ->end()
                ->arrayNode('conf')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('dir')->end()
                            ->enumNode('type')->values(['file', 'image', 'media'])->end()
                            ->booleanNode('tree')->end()
                            ->scalarNode('twig_extension')->end()
                            ->booleanNode('cachebreaker')->defaultValue(true)->end()
                            ->enumNode('view')->values(['thumbnail', 'list'])->defaultValue('list')->end()
                            ->scalarNode('regex')->end()
                            ->scalarNode('service')->end()
                            ->scalarNode('accept')->end()
                            ->arrayNode('upload')
                                ->children()
                                    ->integerNode('min_file_size')->end()
                                    ->integerNode('max_file_size')->end()
                                    ->integerNode('max_width')->end()
                                    ->integerNode('max_height')->end()
                                    ->integerNode('min_width')->end()
                                    ->integerNode('min_height')->end()
                                    ->integerNode('image_library')->end()
                                    ->arrayNode('image_versions')
                                        ->prototype('array')
                                            ->children()
                                                ->booleanNode('auto_orient')->end()
                                                ->booleanNode('crop')->end()
                                                ->integerNode('max_width')->end()
                                                ->integerNode('max_height')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->booleanNode('override')->defaultValue(false)->end()
                                ->end()
                            ->end()
                            ->arrayNode('permissions')
                                ->children()
                                    ->scalarNode('upload_files')->defaultValue('ROLE_USER')->end()
                                    ->scalarNode('remove_files')->defaultValue('ROLE_USER')->end()
                                    ->scalarNode('rename_files')->defaultValue('ROLE_USER')->end()
                                    ->scalarNode('create_folders')->defaultValue('ROLE_USER')->end()
                                    ->scalarNode('remove_folders')->defaultValue('ROLE_USER')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
