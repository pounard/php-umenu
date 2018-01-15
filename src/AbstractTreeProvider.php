<?php

namespace MakinaCorpus\Umenu;

use Psr\SimpleCache\CacheInterface;

/**
 * Loads trees.
 *
 * @todo
 *   - This seriously need to be fixed performance-wise
 *   - wipe out cache
 *   - implement lru for cache (max 10 or more? items)
 *   - implement per site all trees preload
 *   - implement per site / role trees preload
 */
abstract class AbstractTreeProvider implements TreeProviderInterface
{
    private $cache;
    private $perPageTree = [];
    private $loadedTrees = [];

    /**
     * Allow tree cache
     */
    public function setCacheBackend(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Load tree items
     *
     * @param int $menuId
     *
     * @return TreeItem[]
     */
    abstract protected function loadTreeItems($menuId);

    /**
     * Load tree items
     *
     * @param int $pageId
     *   Conditions that applies to the menu storage
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return string[]
     *   List of menu identifiers
     */
    abstract protected function findAllMenuFor($pageId, array $conditions = []);

    /**
     * Load tree items
     *
     * @param TreeItem[]
     *   TreeItem to check access for
     * @param int $userId
     *   Current user identifier to check access for
     *
     * @return TreeItem[]
     *   List of menu items the given user can access
     */
    protected function ensureAccessOf(array $items, $userId) : array
    {
        return $items; // Default is no possible access checks
    }

    /**
     * {@inheritdoc}
     */
    public function findTreeForPage($pageId, array $conditions = [])
    {
        // Not isset() here because result can null (no tree found)
        if (array_key_exists($pageId, $this->perPageTree)) {
            return $this->perPageTree[$pageId];
        }

        $menuIdList = $this->findAllMenuFor($pageId, $conditions);

        if ($menuIdList) {
            // Arbitrary take the first
            // @todo later give more control to this for users
            return $this->perPageTree[$pageId] = reset($menuIdList);
        }

        $this->perPageTree[$pageId] = null;
    }

    /**
     * @inheritdoc
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false)
    {
        if ($withAccess && null === $userId) {
            throw new \InvalidArgumentException("loading menu with access checks needs the user identifier");
        }

        $doCache = false;
        $cacheId = null;

        if (!$withAccess) {
            if ($this->cache) {
                $cacheId = 'umenu_tree_' . $menuId;
                $cached = $this->cache->get($cacheId);

                if ($cached && $cached->data instanceof Tree) {
                    return $cached->data;
                }

                $doCache = true;
            }
        }

        $items = $this->loadTreeItems($menuId);

        if ($withAccess) {
            $items = $this->ensureAccessOf($items, $userId);
        }

        $tree = new Tree($items, $menuId, $relocateOrphans);

        if ($doCache) {
            $this->cache->set($cacheId, $tree);
        }

        return $tree;
    }
}
