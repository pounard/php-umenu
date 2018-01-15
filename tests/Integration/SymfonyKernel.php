<?php

namespace MakinaCorpus\Umenu\Tests\Integration;

use Goat\Bundle\GoatBundle;
use MakinaCorpus\Umenu\MenuBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class SymfonyKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new GoatBundle(),
            new MenuBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
            ]);
            $container->loadFromExtension('goat', [
                'connection' => [
                    'readwrite' => [
                        'host' => getenv('EXT_PGSQL_DSN'),
                        'user' => getenv('EXT_PGSQL_PASSWORD'),
                        'password' => getenv('EXT_PGSQL_USERNAME'),
                        'charset' => 'UTF-8',
                    ],
                ],
            ]);
            $container->loadFromExtension('umenu', [
                'cache' => false,
                'driver' => 'goat',
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/'.str_replace('\\', '-', get_class($this)).'/cache/'.$this->environment;
    }
}
