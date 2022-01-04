<?php

namespace Artgris\Bundle\FileManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class ArtgrisFileManagerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        
        $configuration = new Configuration($container->getParameter('kernel.project_dir'));
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('artgris_file_manager', $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
