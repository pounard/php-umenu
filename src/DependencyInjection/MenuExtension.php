<?php

namespace MakinaCorpus\Umenu\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MenuExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['driver'])) {
            return;
        }

        $doCache = false;
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));

        if ($config['cache']) {
            // @todo
            //$loader->load('cache.yml');
            $doCache = true;
        }

        switch ($config['driver']) {

            case 'drupal':
                $loader->load('drupal.yml');
                if ($doCache) {
                    // @todo
                }
                break;

            case 'goat':
                $loader->load('goat.yml');
                if ($doCache) {
                    // @todo
                }
                break;

            default:
                throw new \InvalidArgumentException(sprintf("%s driver is not a supported driver", $config['driver']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'umenu';
    }
}
