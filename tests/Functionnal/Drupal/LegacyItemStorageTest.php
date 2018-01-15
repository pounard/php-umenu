<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Drupal\LegacyTreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\AbstractItemStorageTest;

class LegacyItemStorageTest extends AbstractItemStorageTest
{
    use DrupalMenuTestTrait;

    public function setUp()
    {
        $this->markTestSkipped("Drupal is not bootstrapped yet");
    }

    protected function getItemStorage() : ItemStorageInterface
    {
        return new LegacyItemStorageTest($this->getDatabaseConnection());
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        return new LegacyTreeProvider($this->getDatabaseConnection());
    }
}
