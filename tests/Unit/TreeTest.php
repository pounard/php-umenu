<?php

namespace MakinaCorpus\Umenu\Tests\Unit;

use MakinaCorpus\Umenu\Tree;
use MakinaCorpus\Umenu\TreeItem;
use PHPUnit\Framework\TestCase;

/**
 * Test data objects accessors basics for coverage purpose
 */
class TreeTest extends TestCase
{
    private function creatMenuTree(bool $relocateOrphans = false) : Tree
    {
        $createItem = \Closure::bind(function (TreeItem $item, $id, $pageId, $parentId, $weight) {
            $item->id = $id;
            $item->page_id = $pageId;
            $item->parent_id = $parentId;
            $item->weight = $weight;
            return $item;
        }, null, TreeItem::class);

        $items = [
            $createItem(new TreeItem(), 1, 100, null, 1),
            $createItem(new TreeItem(), 2, 101, null, 2),
            $createItem(new TreeItem(), 3, 102, 2, 3),
            $createItem(new TreeItem(), 4, 103, 2, 4),
            $createItem(new TreeItem(), 5, 101, 4, 5),
            $createItem(new TreeItem(), 6, 103, 666 /* orphan */, 1),
            $createItem(new TreeItem(), 7, 104, 6, 0),
        ];

        return new Tree($items, 1, $relocateOrphans);
    }

    public function testBasics()
    {
        $tree = $this->creatMenuTree(false);

        $this->assertFalse($tree->isEmpty());

        // @todo how to correctly test arrays?
        // $this->assertSame([100, 101, 102, 103, 101], $tree->getChildrenPageIdList());
    }

    public function testGetItemById()
    {
        $tree = $this->creatMenuTree(false);
        $this->assertSame(5, count($tree->getAll()));
        $this->assertSame(2, $tree->getChildCount());

        $item2 = $tree->getItemById(2);
        $this->assertSame(2, $item2->getId());
        $this->assertSame(101, $item2->getPageId());
        $this->assertSame(2, $item2->getChildCount());

        try {
            $tree->getItemById(6);
            $this->fail("this should have thrown an exception");
        } catch (\InvalidArgumentException $e) {
        }

        try {
            $tree->getItemById(7);
            $this->fail("this should have thrown an exception");
        } catch (\InvalidArgumentException $e) {
        }
    }

    public function testGetItemByIdWithOrphanedRelocated()
    {
        $tree = $this->creatMenuTree(true);
        $this->assertSame(7, count($tree->getAll()));
        $this->assertSame(3, $tree->getChildCount());

        $item2 = $tree->getItemById(2);
        $this->assertSame(2, $item2->getId());

        $item6 = $tree->getItemById(6);
        $this->assertSame(6, $item6->getId());
        $this->assertSame(1, $item6->getChildCount());

        $item7 = $tree->getItemById(7);
        $this->assertSame(7, $item7->getId());
        $this->assertSame(6, $item7->getParentId());
        $this->assertSame(0, $item7->getChildCount());

        $children = $item6->getChildren();
        $this->assertSame($item7, reset($children));
    }

    public function testGetItemsFor()
    {
        $tree = $this->creatMenuTree(false);

        $this->assertTrue($tree->hasPageItems(101));
        $this->assertFalse($tree->hasPageItems(666));
        $this->assertFalse($tree->hasPageItems(104));

        $this->assertCount(1, $tree->getItemsPerPage(100));
        $this->assertCount(1, $tree->getItemsPerPage(103));
        $this->assertCount(0, $tree->getItemsPerPage(104));
    }

    public function testGetItemsForWithOrphans()
    {
        $tree = $this->creatMenuTree(true);

        $this->assertTrue($tree->hasPageItems(101));
        $this->assertFalse($tree->hasPageItems(666));
        $this->assertTrue($tree->hasPageItems(104));

        $this->assertCount(1, $tree->getItemsPerPage(100));
        $this->assertCount(2, $tree->getItemsPerPage(103));
        $this->assertCount(1, $tree->getItemsPerPage(104));
    }

    public function testTrail()
    {
        $tree = $this->creatMenuTree(false);

        // OK with top level
        $this->assertSame(1, $tree->getMostRevelantItemForPage(100)->getId());

        // Always the first
        $this->assertSame(2, $tree->getMostRevelantItemForPage(101)->getId());
        $this->assertSame(4, $tree->getMostRevelantItemForPage(103)->getId());

        $trail = $tree->getMostRevelantTrailForPage(103);
        $this->assertCount(2, $trail);
        $this->assertSame(2, $trail[0]->getId());
        $this->assertSame(4, $trail[1]->getId());

        $trail = $tree->getMostRevelantTrailForPage(666);
        $this->assertCount(0, $trail);

        $tree = $this->creatMenuTree(true);

        // Always the one with the least depth
        // @todo implement this properly
        // $this->assertSame(7, $tree->getMostRevelantItemForPage(103)->getId());
    }
}
