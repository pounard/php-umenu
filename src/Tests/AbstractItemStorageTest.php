<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Umenu\AbstractTreeProvider;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeBase;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;

abstract class AbstractItemStorageTest extends AbstractDrupalTest
{
    use SiteTestTrait; // @todo

    private $menuName;

    protected function tearDown()
    {
        if ($this->menuName) {
            $this->getDatabaseConnection()->query("DELETE FROM {umenu} WHERE name = ?", [$this->menuName]);
            $this->getDatabaseConnection()->query("DELETE FROM {menu_links} WHERE menu_name NOT IN (SELECT name FROM {umenu})");
        }

        parent::tearDown();
    }

    /**
     * @return ItemStorageInterface
     */
    abstract protected function getItemStorage();

    /**
     * @return MenuStorageInterface
     */
    abstract protected function getMenuStorage();

    /**
     * @return AbstractTreeProvider
     */
    abstract protected function getTreeProvider();

    protected function recursiveBuildArray(TreeBase $item)
    {
        $ret = [];

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $ret[$child->getTitle() . '.' . $child->getNodeId() . '.' . $child->getId()] = $this->recursiveBuildArray($child);
            }
        }

        return $ret;
    }

    public function testAll()
    {
        $treeProvider = $this->getTreeProvider();
        $itemStorage = $this->getItemStorage();

        $site = $this->createDrupalSite();
        $menu = $this->getMenuStorage()->create($this->menuName = uniqid('test_item_storage'));
        $menuId = $menu['id'];

        /*
         * Build:
         *   z
         *   a
         *   a/0
         *   a/1
         *   a/2
         *   a/3
         *   a/4
         *   b
         *   c
         *   d
         */

        // INSERT TOP LEVEL
        $nodeB = $this->createDrupalNode('test', $site);
        $itemB = $itemStorage->insert($menuId, $nodeB->id(), 'b');

        // INSERT AFTER NO PARENT
        $nodeD = $this->createDrupalNode('test', $site);
        $itemD = $itemStorage->insertAfter($itemB, $nodeD->id(), 'd');

        // INSERT BEFORE NO PARENT
        $nodeZ = $this->createDrupalNode('test', $site);
        $itemZ = $itemStorage->insertBefore($itemB, $nodeZ->id(), 'z');

        // INSERT AFTER NO PARENT PUSH OTHERS
        $nodeC = $this->createDrupalNode('test', $site);
        $itemC = $itemStorage->insertAfter($itemB, $nodeC->id(), 'c');

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insertBefore($itemB, $nodeA->id(), 'a');

        // INSERT CHILD
        $nodeA2 = $this->createDrupalNode('test', $site);
        $itemA2 = $itemStorage->insertAsChild($itemA, $nodeA2->id(), 'a2');

        // INSERT CHILD BEFORE
        $nodeA0 = $this->createDrupalNode('test', $site);
        $itemA0 = $itemStorage->insertBefore($itemA2, $nodeA0->id(), 'a0');

        // INSERT CHILD BEFORE PUSH OTHERS
        $nodeA1 = $this->createDrupalNode('test', $site);
        $itemA1 = $itemStorage->insertBefore($itemA2, $nodeA1->id(), 'a1');

        // INSERT CHILD AFTER
        $nodeA4 = $this->createDrupalNode('test', $site);
        $itemA4 = $itemStorage->insertAfter($itemA2, $nodeA4->id(), 'a4');

        // INSERT CHILD AFTER PUSH OTHERS
        $nodeA3 = $this->createDrupalNode('test', $site);
        $itemA3 = $itemStorage->insertAfter($itemA2, $nodeA3->id(), 'a3');

        // And now, test everything in the right order
        $tree = $treeProvider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'z.' . $nodeZ->id() . '.' . $itemZ => [],
            'a.' . $nodeA->id() . '.' . $itemA => [
                'a0.' . $nodeA0->id() . '.' . $itemA0 => [],
                'a1.' . $nodeA1->id() . '.' . $itemA1 => [],
                'a2.' . $nodeA2->id() . '.' . $itemA2 => [],
                'a3.' . $nodeA3->id() . '.' . $itemA3 => [],
                'a4.' . $nodeA4->id() . '.' . $itemA4 => [],
            ],
            'b.' . $nodeB->id() . '.' . $itemB => [],
            'c.' . $nodeC->id() . '.' . $itemC => [],
            'd.' . $nodeD->id() . '.' . $itemD => [],
        ];

        $this->assertSame($expected, $actual);
    }
}
