<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Umenu\ItemStorage;
use MakinaCorpus\Umenu\MenuStorage;
use MakinaCorpus\Umenu\TreeProvider;

class DefaultItemStorageTest extends AbstractItemStorageTest
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
        return new TreeProvider($this->getDatabaseConnection());
    }
}
