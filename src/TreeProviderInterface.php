<?php

namespace MakinaCorpus\Umenu;

/**
 * Loads trees.
 */
interface TreeProviderInterface
{
    /**
     * Can this implementation clone trees
     *
     * @return boolean
     */
    public function mayCloneTree();

    /**
     * Clone full tree in given menu
     *
     * @param int $menuId
     * @param Tree $tree
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneTreeIn($menuId, Tree $tree);

    /**
     * Build tree
     *
     * @param int $menuId
     *   Menu identifier
     * @param boolean $withAccess
     *   Should this check access when loading tree
     * @param int $userId
     *   User account identifier for access checks
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false, $userId = null);
}
