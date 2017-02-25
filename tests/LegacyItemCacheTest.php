<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Umenu\DrupalMenuStorage;
use MakinaCorpus\Umenu\Legacy\LegacyItemStorage;
use MakinaCorpus\Umenu\Legacy\LegacyTreeProvider;

class LegacyItemCacheTest extends AbstractCacheTest
{
    protected function getItemStorage()
    {
        return new LegacyItemStorage($this->getDatabaseConnection());
    }

    protected function getMenuStorage()
    {
        return new DrupalMenuStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider()
    {
        $treeProvider = new LegacyTreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getCacheBackend());

        return $treeProvider;
    }
}
