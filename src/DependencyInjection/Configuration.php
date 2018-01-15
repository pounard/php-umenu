<?php

namespace MakinaCorpus\Umenu\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('umenu');

        $rootNode
            ->children()
                ->arrayNode('cache')
                    ->info('Enable cache over menu trees')
                    ->canBeEnabled()
                ->end()
                ->enumNode('driver')
                    ->defaultNull()
                    ->values(['goat', 'drupal'])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
