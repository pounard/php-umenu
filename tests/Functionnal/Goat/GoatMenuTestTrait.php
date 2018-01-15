<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Goat;

use Goat\Testing\GoatTestTrait;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Goat\ItemStorage;
use MakinaCorpus\Umenu\Bridge\Goat\MenuStorage;
use MakinaCorpus\Umenu\Bridge\Goat\TreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\MenuTestTrait;

trait GoatMenuTestTrait
{
    use GoatTestTrait;
    use MenuTestTrait; /* {
        MenuTestTrait::createPage as parentCreatePage;
        MenuTestTrait::createSite as parentCreateSite;
    } */

    protected function getItemStorage() : ItemStorageInterface
    {
        return new ItemStorage($this->getRunner());
    }

    protected function getMenuStorage() : MenuStorageInterface
    {
        return new MenuStorage($this->getRunner());
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        return new TreeProvider($this->getRunner());
    }
}
