<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use MakinaCorpus\Umenu\CachedItemStorageProxy;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Drupal\ItemStorage;
use MakinaCorpus\Umenu\Bridge\Drupal\TreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\AbstractCacheTest;

class DefaultItemCacheTest extends AbstractCacheTest
{
    use DrupalMenuTestTrait;

    protected function getItemStorage() : ItemStorageInterface
    {
        return new CachedItemStorageProxy(
            new ItemStorage($this->getDatabaseConnection()),
            $this->getCacheBackend()
        );
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        $treeProvider = new TreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}
