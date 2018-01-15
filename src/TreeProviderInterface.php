<?php

namespace MakinaCorpus\Umenu;

/**
 * Loads trees.
 */
interface TreeProviderInterface
{
    /**
     * Find most revelant tree for page
     *
     * Equivalent of Drupal's menu_link_get_preferred().
     *
     * This method must always prefer menus with the 'is_main' property set
     * before others, to get a consitent behavior.
     *
     * @param int $pageId
     *   Conditions that applies to the menu storage
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return int
     *   Menu identifier, that you then may load using the buildTree() method
     */
    public function findTreeForPage($pageId, array $conditions = []);

    /**
     * Build tree
     *
     * @param int $menuId
     *   Menu identifier
     * @param boolean $withAccess
     *   Should this check access when loading tree
     * @param int $userId
     *   User account identifier for access checks
     * @param boolean $relocateOrphans
     *   When a parent is not visible nor accessible, should this tree
     *   relocate children to the menu root
     *
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false);
}
