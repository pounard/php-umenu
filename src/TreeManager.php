<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Session\AccountInterface;

class TreeManager
{
    private $menuStorage;
    private $itemStorage;
    private $provider;
    private $currentUser;
    private $cache = [];

    public function __construct(
        MenuStorageInterface $menuStorage,
        ItemStorageInterface $itemStorage,
        TreeProviderInterface $provider,
        AccountInterface $currentUser
    ) {
        $this->menuStorage = $menuStorage;
        $this->itemStorage = $itemStorage;
        $this->provider = $provider;
        $this->currentUser = $currentUser;
    }

    /**
     * @return TreeProviderInterface
     */
    public function getTreeProvider()
    {
        return $this->provider;
    }

    /**
     * @return MenuStorageInterface
     */
    public function getMenuStorage()
    {
        return $this->menuStorage;
    }

    /**
     * @return ItemStorageInterface
     */
    public function getItemStorage()
    {
        return $this->itemStorage;
    }

    /**
     * Internal recursion for clone tree
     *
     * @param int $menuId
     * @param TreeBase $item
     * @param TreeBase $parent
     */
    private function cloneTreeRecursion($menuId, TreeBase $item, $parentId = null)
    {
        if ($item->hasChildren()) {

            $previous = null;

            foreach ($item->getChildren() as $child) {

                if ($previous) {
                    $previous = $this->itemStorage->insertAfter($previous, $child->getNodeId(), $child->getTitle(), $child->getDescription());
                } else if ($parentId) {
                    $previous = $this->itemStorage->insertAsChild($parentId, $child->getNodeId(), $child->getTitle(), $child->getDescription());
                } else {
                    $previous = $this->itemStorage->insert($menuId, $child->getNodeId(), $child->getTitle(), $child->getDescription());
                }

                $this->cloneTreeRecursion($menuId, $child, $previous);
            }
        }
    }

    /**
     * Clone full menu into a new menu within the given site
     *
     * @param int|string $menuId
     *   Menu name or menu identifier
     * @param int $siteId
     *   Target site identifier
     * @param string $name
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneMenu($menuId, $siteId, $name)
    {
        $source = $this->menuStorage->load($menuId);
        $values = [
            'title'       => $source->getTitle(),
            'description' => $source->getDescription(),
            'site_id'     => $siteId,
        ];
        $target = $this->menuStorage->create($name, $values);

        return $this->cloneTreeIn($target->getId(), $this->buildTree($menuId));
    }

    /**
     * Clone full menu into a new menu within the given site
     *
     * @param int|string $sourceMenuId
     *   Menu name or menu identifier
     * @param int|string $targetMenuId
     *   Menu name or menu identifier
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneMenuIn($sourceMenuId, $targetMenuId)
    {
        $source = $this->menuStorage->load($sourceMenuId);
        $target = $this->menuStorage->load($targetMenuId);

        return $this->cloneTreeIn($target->getId(), $this->buildTree($source->getId()));
    }

    /**
     * Clone full tree in given menu
     *
     * This is the default implementation, but the TreeProvider might implement
     * it in a custom and more efficient way if possible.
     *
     * @param int|string $menuId
     *   Menu name or menu identifier
     * @param Tree $tree
     *   Source tree to duplicate
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneTreeIn($menuId, Tree $tree)
    {
        if ($this->provider->mayCloneTree()) {
            return $this->provider->cloneTreeIn($menuId, $tree);
        }

        $this->cloneTreeRecursion($menuId, $tree);

        return $this->provider->buildTree($menuId, false);
    }

    /**
     * Alias of TreeProviderInterface::buildTree()
     *
     * @param int|string $menuId
     *   Menu name or menu identifier
     * @param boolean $withAccess
     *   If set to true, menu will only container visible items for current user
     * @param boolean $relocateOrphans
     *   When a parent is not visible nor accessible, should this tree
     *   relocate children to the menu root
     *
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false, $relocateOrphans = false, $resetCache = false)
    {
        if (!is_numeric($menuId)) {
            $menuId = $this->menuStorage->load($menuId)->getId();
        }

        if (!$resetCache && isset($this->cache[$menuId][(int)$withAccess])) {
            return $this->cache[$menuId][(int)$withAccess];
        }

        return $this->cache[$menuId][(int)$withAccess] = $this
            ->getTreeProvider()
            ->buildTree($menuId, $withAccess, $this->currentUser->id(), $relocateOrphans)
        ;
    }
}
