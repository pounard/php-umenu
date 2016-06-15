<?php

namespace MakinaCorpus\Umenu;

/**
 * This class is actually just a pure hack that allows the tree and tree items
 * classes to use the properties from one another, for better encapsulation.
 */
class TreeBase
{
    /**
     * @var TreeItem[]
     */
    protected $children = [];

    /**
     * Get children
     *
     * @return TreeItem[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Has this object children
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }
}
