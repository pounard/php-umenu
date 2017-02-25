<?php

namespace MakinaCorpus\Umenu\Tests;

use Drupal\Core\Cache\CacheBackendInterface;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Umenu\CachedItemStorageProxy;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;

/**
 * Cache and cache invalidation unit testing
 */
abstract class AbstractCacheTest extends AbstractDrupalTest
{
    use SiteTestTrait; // @todo

    private $menus;

    protected function tearDown()
    {
        if ($this->menus) {
            foreach ($this->menus as $name) {
                $this->getDatabaseConnection()->query("DELETE FROM {umenu} WHERE name = ?", [$name]);
            }
        }

        $this->getDatabaseConnection()->query("DELETE FROM {menu_links} WHERE menu_name NOT IN (SELECT name FROM {umenu})");

        $this->eraseAllData();

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
     * @return TreeProviderInterface
     */
    abstract protected function getTreeProvider();

    /**
     * @return CacheBackendInterface
     */
    protected function getCacheBackend()
    {
        return $this->getDrupalContainer()->get('cache.default');
    }

    /**
     * @return ItemStorageInterface
     */
    protected function getCacheAwareItemStorage()
    {
        return new CachedItemStorageProxy(
            $this->getItemStorage(),
            $this->getCacheBackend()
        );
    }

    /**
     * Invalidation cache is working
     */
    public function testCache()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $site = $this->createDrupalSite();
        $menu = $menuStorage->create($this->menus[] = uniqid('test_item_storage'));
        $menuId = $menu->getId();
        // This one is empty
        $tree = $provider->buildTree($menuId, false);

        // Do anything over the menu
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insert($menuId, $nodeA->id(), 'b');
        // Reload it, it should have remain cached
        $tree = $provider->buildTree($menuId, false);
        $this->assertFalse($tree->hasNodeItems($nodeA->id()));
        $this->assertTrue($tree->isEmpty());
    }

    /**
     * Invalidation testing
     */
    public function testInvalidation()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getCacheAwareItemStorage();

        $site = $this->createDrupalSite();
        $menu = $menuStorage->create($this->menus[] = uniqid('test_item_storage'));
        $menuId = $menu->getId();

        // INSERT TOP LEVEL
        $nodeB = $this->createDrupalNode('test', $site);
        $itemB = $itemStorage->insert($menuId, $nodeB->id(), 'b');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeB->id()));

        // INSERT AFTER NO PARENT PUSH OTHERS
        $nodeC = $this->createDrupalNode('test', $site);
        $itemC = $itemStorage->insertAfter($itemB, $nodeC->id(), 'c');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeC->id()));

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $nodeA = $this->createDrupalNode('test', $site);
        $itemA = $itemStorage->insertBefore($itemB, $nodeA->id(), 'a');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA->id()));

        // INSERT CHILD
        $nodeA2 = $this->createDrupalNode('test', $site);
        $itemA2 = $itemStorage->insertAsChild($itemA, $nodeA2->id(), 'a2');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA2->id()));

        // INSERT CHILD BEFORE PUSH OTHERS
        $nodeA1 = $this->createDrupalNode('test', $site);
        $itemA1 = $itemStorage->insertBefore($itemA2, $nodeA1->id(), 'a1');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA1->id()));

        // INSERT CHILD AFTER PUSH OTHERS
        $nodeA3 = $this->createDrupalNode('test', $site);
        $itemA3 = $itemStorage->insertAfter($itemA2, $nodeA3->id(), 'a3');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasNodeItems($nodeA3->id()));

        // DELETE
        $itemStorage->delete($itemA1);
        $tree = $provider->buildTree($menuId, false);
        $this->assertFalse($tree->hasNodeItems($nodeA1->id()));

        // UPDATE
        $itemStorage->update($itemA, $nodeA->id(), 'new title');
        $tree = $provider->buildTree($menuId, false);
        $updatedItemA = $tree->getItemById($itemA);
        $this->assertSame('new title', $updatedItemA->getTitle());

        // Reparent 'b' under 'a', should be last
        $itemStorage->moveAsChild($itemB, $itemA);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'c' after 'a/2'
        $itemStorage->moveAfter($itemC, $itemA2);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a3' to root
        $itemStorage->moveToRoot($itemA3);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a2' before 'a'
        $itemStorage->moveBefore($itemA2, $itemA);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'c' under 'a3'
        $itemStorage->moveAsChild($itemC, $itemA3);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning

        // Move 'a' before 'c'
        $itemStorage->moveBefore($itemA, $itemC);
        $tree = $provider->buildTree($menuId, false);
        // @todo test positionning
    }
}
