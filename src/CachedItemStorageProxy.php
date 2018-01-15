<?php

namespace MakinaCorpus\Umenu;

use Psr\SimpleCache\CacheInterface;

/**
 * Item storage;
 */
class CachedItemStorageProxy implements ItemStorageInterface
{
    private $nested;
    private $cache;

    /**
     * Default constructor
     */
    public function __construct(ItemStorageInterface $nested, CacheInterface $cache)
    {
        $this->nested = $nested;
        $this->cache = $cache;
    }

    /**
     * Get menu cache identifier
     *
     * @param int $menuId
     *
     * @return string
     */
    private function getCacheId(int $menuId) : string
    {
        return 'umenu_tree_' . $menuId;
    }

    /**
     * Get menu cache identifier from item identifier
     *
     * @param int $itemId
     *
     * @return string
     */
    private function getCacheIdFrom(int $itemId) : string
    {
        return 'umenu_tree_' . $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuIdFor(int $itemId) : int
    {
        return $this->nested->getMenuIdFor($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(int $menuId, int $pageId, string $title, string $description = '') : int
    {
        $ret = $this->nested->insert($menuId, $pageId, $title, $description);
        $this->cache->delete($this->getCacheId($menuId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        $ret = $this->nested->insertAsChild($otherItemId, $pageId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        $ret = $this->nested->insertAfter($otherItemId, $pageId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        $ret = $this->nested->insertBefore($otherItemId, $pageId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($otherItemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $itemId, int $pageId = null, string $title = '', string $description = '')
    {
        $ret = $this->nested->update($itemId, $pageId, $title, $description);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveAsChild($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot(int $itemId)
    {
        $ret = $this->nested->moveToRoot($itemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveAfter($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore(int $itemId, int $otherItemId)
    {
        $ret = $this->nested->moveBefore($itemId, $otherItemId);
        $this->cache->delete($this->getCacheIdFrom($itemId));

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $itemId)
    {
        // For deletion, fetch cache identifier first
        $cacheId = $this->getCacheIdFrom($itemId);
        $ret = $this->nested->delete($itemId);
        $this->cache->delete($cacheId);

        return $ret;
    }
}
