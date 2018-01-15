<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Goat;

use MakinaCorpus\Umenu\CachedItemStorageProxy;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Goat\ItemStorage;
use MakinaCorpus\Umenu\Bridge\Goat\TreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\AbstractCacheTest;

class DefaultItemCacheTest extends AbstractCacheTest
{
    use GoatMenuTestTrait;

    protected function getItemStorage() : ItemStorageInterface
    {
        return new CachedItemStorageProxy(
            new ItemStorage($this->getRunner()),
            $this->getCacheBackend()
        );
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        $treeProvider = new TreeProvider($this->getRunner());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}
