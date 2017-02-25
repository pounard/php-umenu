<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Umenu\ItemStorage;
use MakinaCorpus\Umenu\MenuStorage;
use MakinaCorpus\Umenu\TreeProvider;

class DefaultItemCacheTest extends AbstractCacheTest
{
    protected function getItemStorage()
    {
        return new ItemStorage($this->getDatabaseConnection());
    }

    protected function getMenuStorage()
    {
        return new MenuStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider()
    {
        $treeProvider = new TreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}
