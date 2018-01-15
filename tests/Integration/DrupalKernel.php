<?php

namespace MakinaCorpus\Umenu\Tests\Integration;

use MakinaCorpus\Umenu\MenuBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\DependencyInjection\Definition;

class DrupalKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new MenuBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $container)
    {
        // Register base Drupal services
        $container->addDefinitions([
            (new Definition('current_user'))
                ->setPublic(true)
                ->setClass('\Drupal\Core\Session\UserSession'),
            (new Definition('database'))
                ->setPublic(true)
                ->setClass('\DatabaseConnection')
                ->setFactory('\Database::getConnection()')
        ]);
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
            $container->loadFromExtension('umenu', [
                'cache' => false,
                'driver' => 'drupal',
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
