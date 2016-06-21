<?php

namespace MakinaCorpus\Umenu\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\DrupalMenuStorage;
use MakinaCorpus\Umenu\LegacyTreeProvider;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;

class LegacyTreeProviderTest extends AbstractDrupalTest
{
    // FIXME
    use SiteTestTrait;

    protected $menuName;
    protected $menuLinks = [];

    protected function tearDown()
    {
        $this->eraseAllData();

        $this->getMenuStorage()->delete($this->menuName);

        parent::tearDown();
    }

    /**
     * @return TreeProviderInterface
     */
    protected function getTreeProvider()
    {
        return new LegacyTreeProvider($this->getDatabaseConnection());
    }

    /**
     * @return MenuStorageInterface
     */
    protected function getMenuStorage()
    {
        return new DrupalMenuStorage($this->getDatabaseConnection());
    }

    protected function createMenuItem($name, $node, $parent = null)
    {
        $item = [
            'link_path'   => 'node/' . $node->nid,
            'link_title'  => $name,
            'menu_name'   => $this->menuName,
        ];
        if ($parent) {
            if (empty($this->menuLinks[$parent])) {
                throw new \InvalidArgumentException("You are somehow stupid, and parent does not exist");
            }
            $item['plid'] = $this->menuLinks[$parent];
        }
        return $this->menuLinks[$name] = menu_link_save($item);
    }

    public function testOrphanReallocation()
    {
        $this->menuName = uniqid('test');

        $storage  = $this->getMenuStorage();
        $provider = $this->getTreeProvider();
        $menu     = $storage->create($this->menuName, ['title' => 'some title']);
        $menuId   = $menu['id'];

        /*
         * VISBLE a
         * NOPE   a/b
         * VISBLE a/b/c
         * NOPE   a/d
         * NOPE   b
         * VISBLE b/c
         */
        $nodeA    = $this->createDrupalNode('test', null, ['status' => 1]);
        $menuA    = $this->createMenuItem('a', $nodeA);
        $nodeAB   = $this->createDrupalNode('test', null, ['status' => 0]);
        $menuAB   = $this->createMenuItem('a/b', $nodeAB, 'a');
        $nodeAD   = $this->createDrupalNode('test', null, ['status' => 0, 'is_global' => 1]);
        $menuAD  = $this->createMenuItem('a/d', $nodeAD, 'a');
        $nodeABC  = $this->createDrupalNode('test', null, ['status' => 1, 'is_global' => 1]);
        $menuABC  = $this->createMenuItem('a/b/c', $nodeABC, 'a/b');
        $nodeB    = $this->createDrupalNode('test', null, ['status' => 0, 'is_global' => 1]);
        $menuB    = $this->createMenuItem('b', $nodeB);
        $nodeBC   = $this->createDrupalNode('test', null, ['status' => 1]);
        $menuBC   = $this->createMenuItem('b/c', $nodeBC, 'b');

        // No child reallocation
        $tree = $provider->buildTree($menuId, true, 12, false);
        $this->assertTrue($tree->hasNodeItems($nodeA->id()));
        $this->assertFalse($tree->hasNodeItems($nodeAB->id()));
        $this->assertFalse($tree->hasNodeItems($nodeABC->id()));

        // With child reallocation
        $tree = $provider->buildTree($menuId, true, 12, true);
        $this->assertTrue($tree->hasNodeItems($nodeA->id()));
        $this->assertFalse($tree->hasNodeItems($nodeAB->id()));
        $this->assertTrue($tree->hasNodeItems($nodeABC->id()));
    }

    public function testMenuProvider()
    {
        $this->menuName = uniqid('test');

        $storage  = $this->getMenuStorage();
        $provider = $this->getTreeProvider();
        $menu     = $storage->create($this->menuName, ['title' => 'some title']);
        $menuId   = $menu['id'];

        /*
         * VISBLE a
         * VISBLE a/b
         * NOPE   a/b/c
         * NOPE   a/d
         * NOPE   b
         * VISBLE b/c
         */
        $nodeA    = $this->createDrupalNode('test', null, ['status' => 1]);
        $menuA    = $this->createMenuItem('a', $nodeA);
        $nodeAB   = $this->createDrupalNode('test', null, ['status' => 1]);
        $menuAB   = $this->createMenuItem('a/b', $nodeAB, 'a');
        $nodeAD   = $this->createDrupalNode('test', null, ['status' => 0, 'is_global' => 1]);
        $menuAD  = $this->createMenuItem('a/d', $nodeAD, 'a');
        $nodeABC  = $this->createDrupalNode('test', null, ['status' => 0, 'is_global' => 1]);
        $menuABC  = $this->createMenuItem('a/b/c', $nodeABC, 'a/b');
        $nodeB    = $this->createDrupalNode('test', null, ['status' => 0, 'is_global' => 1]);
        $menuB    = $this->createMenuItem('b', $nodeB);
        $nodeBC   = $this->createDrupalNode('test', null, ['status' => 1]);
        $menuBC   = $this->createMenuItem('b/c', $nodeBC, 'b');

        $tree = $provider->buildTree($menuId, false);
        $children = $tree->getChildren();
        $this->assertCount(2, $children);

        $aChildren = $tree->getItemById($menuA)->getChildren();
        $this->assertCount(2, $aChildren);
        $child = array_shift($aChildren);
        $this->assertEquals($menuAB, $child->getId());
        $this->assertEquals('node/' . $nodeAB->nid, $child->getRoute());
        $child = array_shift($aChildren);
        $this->assertEquals($menuAD, $child->getId());
        $this->assertEquals('node/' . $nodeAD->nid, $child->getRoute());

        // And now with access
        /*
         * @todo find a way
         *
        $tree = $provider->buildTree($menuId, true, 112);
        $children = $tree->getChildren();
        $this->assertCount(2, $children);

        $aChildren = $tree->getItemById($menuA)->getChildren();
        $this->assertCount(1, $aChildren); // 'a/d' is not visible
        $child = array_shift($aChildren);
        $this->assertEquals($menuAB, $child->getId());
        $this->assertEquals('node/' . $nodeAB->nid, $child->getRoute());
        $this->assertCount(0, $child->getChildren());

        // 'b/c' is now top-level
        $child = end($children);
        $this->assertEquals($menuBC, $child->getId());
         */
    }
}
