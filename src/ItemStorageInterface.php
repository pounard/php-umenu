<?php

namespace MakinaCorpus\Umenu;

/**
 * Item storage;
 */
interface ItemStorageInterface
{
    /**
     * Get menu identifier for item
     */
    public function getMenuIdFor(int $itemId) : int;

    /**
     * Append new item within menu
     */
    public function insert(int $menuId, int $pageId, string $title, string $description = '') : int;

    /**
     * Append new item as child of the selected item
     */
    public function insertAsChild(int $otherItemId, int $pageId, string $title, string $description = '') : int;

    /**
     * Insert item after another
     */
    public function insertAfter(int $otherItemId, int $pageId, string $title, string $description = '') : int;

    /**
     * Insert item before another
     */
    public function insertBefore(int $otherItemId, int $pageId, string $title, string $description = '') : int;

    /**
     * Update item
     */
    public function update(int $itemId, int $pageId = null, string $title, string $description = '');

    /**
     * Reparent item
     */
    public function moveAsChild(int $itemId, int $otherItemId);

    /**
     * Orphan item
     */
    public function moveToRoot(int $itemId);

    /**
     * Move item after another
     */
    public function moveAfter(int $itemId, int $otherItemId);

    /**
     * Move item before another
     */
    public function moveBefore(int $itemId, int $otherItemId);

    /**
     * Delete item
     */
    public function delete(int $itemId);
}
