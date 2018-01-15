<?php

namespace MakinaCorpus\Umenu;

/**
 * Represents a menu tree in a specific state, using a projection depending upon
 * context, variations can be:
 *   - full tree
 *   - only items visible to user without orphans
 *   - only items visible to user with orphans relocated to root
 */
final class Tree extends TreeBase
{
    private $topLevel = [];
    private $perPage = [];
    private $menuId;
    private $menuName;

    /**
     * Default constructor
     *
     * @param TreeItem[] $items
     * @param int $menuId
     * @param boolean $relocateOrphans
     */
    public function __construct($items = null, $menuId = null, $relocateOrphans = false)
    {
        if ($items) {
            $this->setItems($items, $relocateOrphans);
        }

        $this->menuId = $menuId;
    }

    /**
     * Get menu identifier
     *
     * @return int
     */
    public function getMenuId()
    {
        return $this->menuId;
    }

    /**
     * Get menu name
     *
     * @return string
     */
    public function getMenuName()
    {
        return $this->menuName;
    }

    /**
     * Set items
     *
     * @param TreeItem[] $items
     * @param boolean $relocateOrphans
     */
    public function setItems($items, $relocateOrphans = false)
    {
        // Order is kept from the function input, provider must order them
        // correctly prior to us, weight should not matter to this algorithm.

        // Please note, and this is very important, that it is mandatory to
        // register first all children, because they are supposed to be sorted
        // by weight first, then parent, the second foreach will deal with
        // parenting, but this one will ensure all parents are visible for the
        // second!
        foreach ($items as $item) {
            if (!$item instanceof TreeItem) {
                throw new \InvalidArgumentException(sprintf("items must be instances of %s", TreeItem::class));
            }

            $this->children[$item->getId()] = $item;
        }

        foreach ($this->children as $item) {
            $parentId = $item->getParentId();

            if (!$parentId) {
                $this->topLevel[] = $item;
            } else if (!isset($this->children[$parentId])) {
                if ($relocateOrphans) {
                    $this->topLevel[] = $item;
                } else {
                    // Item is orphan and we don't relocate orphan, we must
                    // exclude this item from tree
                    unset($this->children[$item->getId()]);
                    continue;
                }
            } else {
                // Build the tree by adding the child to its parent, this is
                // allowed because both this class and the TreeItem class are
                // extending the TreeBase class, and properties are protected.
                $this->children[$parentId]->children[$item->getId()] = $item;
            }

            // And we need to be able to fetch those per page too.
            $pageId = $item->getPageId();
            if ($pageId) {
                $this->perPage[$pageId][] = $item;
            }
        }
    }

    /**
     * Get item by identifier
     *
     * @param int $id
     */
    public function getItemById($id)
    {
        if (!isset($this->children[$id])) {
            throw new \InvalidArgumentException(sprintf("item with id %d does not exist", $id));
        }

        return $this->children[$id];
    }

    /**
     * Does the given page has items within this menu
     *
     * @param int $pageId
     *
     * @return boolean
     */
    public function hasPageItems($pageId)
    {
        return !empty($this->perPage[$pageId]);
    }

    /**
     * Get items for the given page
     *
     * @param int $pageId
     *
     * @return TreeItem[]
     */
    public function getItemsPerPage($pageId)
    {
        if (isset($this->perPage[$pageId])) {
            return $this->perPage[$pageId];
        }

        return [];
    }

    /**
     * Get most revelant item for page
     *
     * @param int $pageId
     *
     * @return TreeItem
     *  Can be null if none found
     */
    public function getMostRevelantItemForPage($pageId)
    {
        if (isset($this->perPage[$pageId])) {
            return reset($this->perPage[$pageId]);
        }
    }

    /**
     * Get most revelant trail for page
     *
     * @param int $pageId
     *
     * @return TreeItem[]
     *  Tree items in order, from the top most parent to the page item
     */
    public function getMostRevelantTrailForPage($pageId)
    {
        $trail = [];

        $item = $this->getMostRevelantItemForPage($pageId);
        if (!$item) {
            return $trail;
        }

        $trail[] = $item;

        $parentId = $item->getParentId();
        while (isset($this->children[$parentId])) {
            array_unshift($trail, $item = $this->children[$parentId]);
            $parentId = $item->getParentId();
        }

        return $trail;
    }

    /**
     * Is the current tree empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->children);
    }

    /**
     * Get top level children
     *
     * @return TreeItem[]
     */
    public function getChildren()
    {
        return $this->topLevel;
    }

    /**
     * Count the number of children
     *
     * @return int
     */
    public function getChildCount()
    {
        return count($this->topLevel);
    }

    /**
     * Get all items in a flattened array
     *
     * @return TreeItem[]
     */
    public function getAll()
    {
        return $this->children;
    }
}
