<?php

namespace MakinaCorpus\Umenu;

/**
 * Represents a menu tree, and provide various helpers for manipulating it.
 *
 * A tree in our conception is composed only from nodes.
 *
 * Reason this exists is to abstract ourselves from Drupal menu usage and allow
 * us to completely drop it in the long term.
 */
class Tree extends TreeBase
{
    private $topLevel = [];
    private $perNode = [];
    private $menuId;
    private $menuName;

    /**
     * Default constructor
     *
     * @param TreeItem[] $items
     * @param int $menuId
     */
    public function __construct($items = null, $menuId = null)
    {
        if ($items) {
            $this->setItems($items);
        }

        $this->menuId = $menuId;
    }

    /**
     * Get menu identifier
     *
     * @return id
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
     */
    public function setItems($items)
    {
        // Items must already be ordered
        foreach ($items as $item) {
            if (!$item instanceof TreeItem) {
                throw new \InvalidArgumentException("items must be instances of \MakinaCorpus\Umenu\TreeItem");
            }
            $this->children[$item->getId()] = $item;
        }

        foreach ($this->children as $item) {
            $parentId = $item->getParentId();

            if (!$parentId || !isset($this->children[$parentId])) {
                $this->topLevel[] = $item;
            } else {
                // Build the tree by adding the child to its parent, this is
                // allowed because both this class and the TreeItem class are
                // extending the TreeBase class, and properties are protected.
                $this->children[$parentId]->children[$item->getId()] = $item;
            }

            // And we need to be able to fetch those per node too.
            $nodeId = $item->getNodeId();
            if ($nodeId) {
                $this->perNode[$nodeId][] = $item;
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
     * Does the given node has items within this menu
     *
     * @param int $nodeId
     *
     * @return boolean
     */
    public function hasNodeItems($nodeId)
    {
        return !empty($this->perNode[$nodeId]);
    }

    /**
     * Get items for the given node
     *
     * @param int $nodeId
     *
     * @return TreeItem[]
     */
    public function getItemsPerNode($nodeId)
    {
        if (isset($this->perNode[$nodeId])) {
            return $this->perNode[$nodeId];
        }

        return [];
    }

    /**
     * Get most revelant item for node
     *
     * @param int $nodeId
     *
     * @return TreeItem
     *  Can be null if none found
     */
    public function getMostRevelantItemForNode($nodeId)
    {
        if (isset($this->perNode[$nodeId])) {
            return reset($this->perNode[$nodeId]);
        }
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
