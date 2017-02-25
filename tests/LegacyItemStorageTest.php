<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Umenu\DrupalMenuStorage;
use MakinaCorpus\Umenu\Legacy\LegacyItemStorage;
use MakinaCorpus\Umenu\Legacy\LegacyTreeProvider;

class LegacyItemStorageTest extends AbstractItemStorageTest
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
        return new LegacyTreeProvider($this->getDatabaseConnection());
    }
}
