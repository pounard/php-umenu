<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal;

use PHPUnit\Framework\TestCase;

/**
 * Cache and cache invalidation unit testing
 */
abstract class AbstractCacheTest extends TestCase
{
    use MenuTestTrait;

    /**
     * Invalidation cache is working
     */
    public function testCache()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $siteId = $this->createSite();
        $menu = $menuStorage->create(uniqid('test_item_storage'));
        $menuId = $menu->getId();
        // This one is empty
        $tree = $provider->buildTree($menuId, false);

        // Do anything over the menu
        $pageA = $this->createPage('test', $siteId);
        $itemA = $itemStorage->insert($menuId, $pageA->getId(), 'b');

        $this->assertTrue(true, "at least, there was no error until here");

        // Sorry for doing this, but the ucms_seo module make this test
        // fail since it will wipe out the cache without asking.
        if (function_exists('module_exists') && !module_exists('ucms_seo')) {
            // Reload it, it should have remain cached
            $tree = $provider->buildTree($menuId, false);
            $this->assertFalse($tree->hasPageItems($pageA->getId()));
            $this->assertTrue($tree->isEmpty());
        }
    }

    /**
     * Invalidation testing
     */
    public function testInvalidation()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getCacheAwareItemStorage();

        $siteId = $this->createSite();
        $menu = $menuStorage->create(uniqid('test_item_storage'));
        $menuId = $menu->getId();

        // INSERT TOP LEVEL
        $pageB = $this->createPage('test', $siteId);
        $itemB = $itemStorage->insert($menuId, $pageB->getId(), 'b');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageB->getId()));

        // INSERT AFTER NO PARENT PUSH OTHERS
        $pageC = $this->createPage('test', $siteId);
        $itemC = $itemStorage->insertAfter($itemB, $pageC->getId(), 'c');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageC->getId()));

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $pageA = $this->createPage('test', $siteId);
        $itemA = $itemStorage->insertBefore($itemB, $pageA->getId(), 'a');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageA->getId()));

        // INSERT CHILD
        $pageA2 = $this->createPage('test', $siteId);
        $itemA2 = $itemStorage->insertAsChild($itemA, $pageA2->getId(), 'a2');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageA2->getId()));

        // INSERT CHILD BEFORE PUSH OTHERS
        $pageA1 = $this->createPage('test', $siteId);
        $itemA1 = $itemStorage->insertBefore($itemA2, $pageA1->getId(), 'a1');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageA1->getId()));

        // INSERT CHILD AFTER PUSH OTHERS
        $pageA3 = $this->createPage('test', $siteId);
        $itemA3 = $itemStorage->insertAfter($itemA2, $pageA3->getId(), 'a3');
        $tree = $provider->buildTree($menuId, false);
        $this->assertTrue($tree->hasPageItems($pageA3->getId()));

        // DELETE
        $itemStorage->delete($itemA1);
        $tree = $provider->buildTree($menuId, false);
        $this->assertFalse($tree->hasPageItems($pageA1->getId()));

        // UPDATE
        $itemStorage->update($itemA, $pageA->getId(), 'new title');
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
