<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Drupal\LegacyItemStorage;
use MakinaCorpus\Umenu\Bridge\Drupal\LegacyTreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\AbstractCacheTest;

class LegacyItemCacheTest extends AbstractCacheTest
{
    use DrupalMenuTestTrait;

    public function setUp()
    {
        $this->markTestSkipped("Drupal is not bootstrapped yet");
    }

    protected function getItemStorage() : ItemStorageInterface
    {
        return new LegacyItemStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        $treeProvider = new LegacyTreeProvider($this->getDatabaseConnection());
        $treeProvider->setCacheBackend($this->getDrupal8Cache());

        return $treeProvider;
    }
}
