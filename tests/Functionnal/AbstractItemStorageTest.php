<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal;

use MakinaCorpus\Umenu\TreeBase;
use MakinaCorpus\Umenu\TreeManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractItemStorageTest extends TestCase
{
    use MenuTestTrait;

    protected function setUp()
    {
        parent::setUp();

        $storage = $this->getMenuStorage();
        $storage->delete('a');
        $storage->delete('b');
        $storage->delete('c');
        $storage->delete('d');
        $storage->delete('e');
        $storage->delete('f');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $storage = $this->getMenuStorage();
        $storage->delete('a');
        $storage->delete('b');
        $storage->delete('c');
        $storage->delete('d');
        $storage->delete('e');
        $storage->delete('f');
    }

    protected function recursiveBuildArray(TreeBase $item)
    {
        $ret = [];

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $ret[$child->getTitle() . '.' . $child->getPageId() . '.' . $child->getId()] = $this->recursiveBuildArray($child);
            }
        }

        return $ret;
    }

    protected function recursiveBuildArrayWithoutId(TreeBase $item)
    {
        $ret = [];

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $ret[$child->getTitle() . '.' . $child->getPageId() . '.' . $child->getSiteId()] = $this->recursiveBuildArrayWithoutId($child);
            }
        }

        return $ret;
    }

    public function testAll()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $siteId = $this->createSite();
        $menu = $menuStorage->create('a');
        $menuId = $menu->getId();

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
        $pageB = $this->createPage('test', $siteId);
        $itemB = $itemStorage->insert($menuId, $pageB->getId(), 'b');

        // INSERT AFTER NO PARENT
        $pageD = $this->createPage('test', $siteId);
        $itemD = $itemStorage->insertAfter($itemB, $pageD->getId(), 'd');

        // INSERT BEFORE NO PARENT
        $pageZ = $this->createPage('test', $siteId);
        $itemZ = $itemStorage->insertBefore($itemB, $pageZ->getId(), 'z');

        // INSERT AFTER NO PARENT PUSH OTHERS
        $pageC = $this->createPage('test', $siteId);
        $itemC = $itemStorage->insertAfter($itemB, $pageC->getId(), 'c');

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $pageA = $this->createPage('test', $siteId);
        $itemA = $itemStorage->insertBefore($itemB, $pageA->getId(), 'a');

        // INSERT CHILD
        $pageA2 = $this->createPage('test', $siteId);
        $itemA2 = $itemStorage->insertAsChild($itemA, $pageA2->getId(), 'a2');

        // INSERT CHILD BEFORE
        $pageA0 = $this->createPage('test', $siteId);
        $itemA0 = $itemStorage->insertBefore($itemA2, $pageA0->getId(), 'a0');

        // INSERT CHILD BEFORE PUSH OTHERS
        $pageA1 = $this->createPage('test', $siteId);
        $itemA1 = $itemStorage->insertBefore($itemA2, $pageA1->getId(), 'a1');

        // INSERT CHILD AFTER
        $pageA4 = $this->createPage('test', $siteId);
        $itemA4 = $itemStorage->insertAfter($itemA2, $pageA4->getId(), 'a4');

        // INSERT CHILD AFTER PUSH OTHERS
        $pageA3 = $this->createPage('test', $siteId);
        $itemA3 = $itemStorage->insertAfter($itemA2, $pageA3->getId(), 'a3');

        // And now, test everything in the right order
        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'z.' . $pageZ->getId() . '.' . $itemZ => [],
            'a.' . $pageA->getId() . '.' . $itemA => [
                'a0.' . $pageA0->getId() . '.' . $itemA0 => [],
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
                'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
                'a4.' . $pageA4->getId() . '.' . $itemA4 => [],
            ],
            'b.' . $pageB->getId() . '.' . $itemB => [],
            'c.' . $pageC->getId() . '.' . $itemC => [],
            'd.' . $pageD->getId() . '.' . $itemD => [],
        ];

        $this->assertSame($expected, $actual);

        /*
         * Go for clone test, now that we do have something
         */

        $manager = new TreeManager($menuStorage, $itemStorage, $provider /*, new User() */);

/*
 * @todo
 *    This test will cause trouble because of FKs in Drupal sites schema:
 *    when creating nodes, they are not in the new site, when duplicating
 *    the tree in the new site, node not being attached to it will
 *    make the ucms_item (site_id, node_id) FK to fail on insert.
 *
 *    In normal ucms workflow, nodes will be bulk attached to new site
 *    before menu gets cloned.
 *
        $newSiteId = $this->createSite();
        $otherMenu = $menuStorage->create(uniqid('test_item_storage'), ['site_id' => $newSiteId]);
        $tree = $provider->buildTree($menu->getId());
        $newTree = $manager->cloneTreeIn($otherMenu->getId(), $tree);
        $actual = $this->recursiveBuildArrayWithoutId($newTree);
        $expected = [
            'z.' . $pageZ->getId() . '.' . $newSiteId => [],
            'a.' . $pageA->getId() . '.' . $newSiteId => [
                'a0.' . $pageA0->getId() . '.' . $newSiteId => [],
                'a1.' . $pageA1->getId() . '.' . $newSiteId => [],
                'a2.' . $pageA2->getId() . '.' . $newSiteId => [],
                'a3.' . $pageA3->getId() . '.' . $newSiteId => [],
                'a4.' . $pageA4->getId() . '.' . $newSiteId => [],
            ],
            'b.' . $pageB->getId() . '.' . $newSiteId => [],
            'c.' . $pageC->getId() . '.' . $newSiteId => [],
            'd.' . $pageD->getId() . '.' . $newSiteId => [],
        ];

        $this->assertSame($expected, $actual);
*/
        /*
         * And another clone test
         */

/*
 * @todo
 *   This fails for the exact same reason
 *
        $otherSiteId = $this->createSite();
        $otherTree = $manager->cloneMenu($menuId, $otherSiteId, uniqid('test_item_storage'));

        $actual = $this->recursiveBuildArrayWithoutId($otherTree);
        $expected = [
            'z.' . $pageZ->getId() . '.' . $otherSiteId => [],
            'a.' . $pageA->getId() . '.' . $otherSiteId => [
                'a0.' . $pageA0->getId() . '.' . $otherSiteId => [],
                'a1.' . $pageA1->getId() . '.' . $otherSiteId => [],
                'a2.' . $pageA2->getId() . '.' . $otherSiteId => [],
                'a3.' . $pageA3->getId() . '.' . $otherSiteId => [],
                'a4.' . $pageA4->getId() . '.' . $otherSiteId => [],
            ],
            'b.' . $pageB->getId() . '.' . $otherSiteId => [],
            'c.' . $pageC->getId() . '.' . $otherSiteId => [],
            'd.' . $pageD->getId() . '.' . $otherSiteId => [],
        ];

        $this->assertSame($expected, $actual);
 */
    }

    public function testMove()
    {
        $provider = $this->getTreeProvider();
        $menuStorage = $this->getMenuStorage();
        $itemStorage = $this->getItemStorage();

        $siteId = $this->createSite();
        $menu = $menuStorage->create('a');
        $menuId = $menu->getId();

        /*
         * Build:
         *   a
         *   a/1
         *   a/2
         *   a/3
         *   b
         *   c
         */

        // INSERT TOP LEVEL
        $pageB = $this->createPage('test', $siteId);
        $itemB = $itemStorage->insert($menuId, $pageB->getId(), 'b');

        // INSERT AFTER NO PARENT PUSH OTHERS
        $pageC = $this->createPage('test', $siteId);
        $itemC = $itemStorage->insertAfter($itemB, $pageC->getId(), 'c');

        // INSERT BEFORE NO PARENT PUSH OTHERS
        $pageA = $this->createPage('test', $siteId);
        $itemA = $itemStorage->insertBefore($itemB, $pageA->getId(), 'a');

        // INSERT CHILD
        $pageA2 = $this->createPage('test', $siteId);
        $itemA2 = $itemStorage->insertAsChild($itemA, $pageA2->getId(), 'a2');

        // INSERT CHILD BEFORE PUSH OTHERS
        $pageA1 = $this->createPage('test', $siteId);
        $itemA1 = $itemStorage->insertBefore($itemA2, $pageA1->getId(), 'a1');

        // INSERT CHILD AFTER PUSH OTHERS
        $pageA3 = $this->createPage('test', $siteId);
        $itemA3 = $itemStorage->insertAfter($itemA2, $pageA3->getId(), 'a3');

        // And now, test everything in the right order
        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $pageA->getId() . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
                'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
            ],
            'b.' . $pageB->getId() . '.' . $itemB => [],
            'c.' . $pageC->getId() . '.' . $itemC => [],
        ];
        $this->assertSame($expected, $actual);

        /*
         * Go for some moves:
         *   a
         *   a/1
         *   a/2
         *   a/3
         *   b
         *   c
         */

        // Reparent 'b' under 'a', should be last
        $itemStorage->moveAsChild($itemB, $itemA);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $pageA->getId() . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
                'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
                'b.'  . $pageB->getId()  . '.' . $itemB => [],
            ],
            'c.' . $pageC->getId() . '.' . $itemC => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'c' after 'a/2'
        $itemStorage->moveAfter($itemC, $itemA2);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $pageA->getId() . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
                'c.'  . $pageC->getId()  . '.' . $itemC => [],
                'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
                'b.'  . $pageB->getId()  . '.' . $itemB => [],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a3' to root
        $itemStorage->moveToRoot($itemA3);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a.' . $pageA->getId() . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
                'c.'  . $pageC->getId()  . '.' . $itemC => [],
                'b.'  . $pageB->getId()  . '.' . $itemB => [],
            ],
            'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a2' before 'a'
        $itemStorage->moveBefore($itemA2, $itemA);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
            'a.'  . $pageA->getId()  . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'c.'  . $pageC->getId()  . '.' . $itemC => [],
                'b.'  . $pageB->getId()  . '.' . $itemB => [],
            ],
            'a3.' . $pageA3->getId() . '.' . $itemA3 => [],
        ];
        $this->assertSame($expected, $actual);

        // Move 'c' under 'a3'
        $itemStorage->moveAsChild($itemC, $itemA3);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
            'a.'  . $pageA->getId()  . '.' . $itemA => [
                'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                'b.'  . $pageB->getId()  . '.' . $itemB => [],
            ],
            'a3.' . $pageA3->getId() . '.' . $itemA3 => [
                'c.'  . $pageC->getId()  . '.' . $itemC => [],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Move 'a' before 'c'
        $itemStorage->moveBefore($itemA, $itemC);

        $tree = $provider->buildTree($menuId, false);
        $actual = $this->recursiveBuildArray($tree);
        $expected = [
            'a2.' . $pageA2->getId() . '.' . $itemA2 => [],
            'a3.' . $pageA3->getId() . '.' . $itemA3 => [
                'a.'  . $pageA->getId()  . '.' . $itemA => [
                    'a1.' . $pageA1->getId() . '.' . $itemA1 => [],
                    'b.'  . $pageB->getId()  . '.' . $itemB => [],
                ],
                'c.'  . $pageC->getId()  . '.' . $itemC => [],
            ],
        ];
        $this->assertSame($expected, $actual);
    }
}
