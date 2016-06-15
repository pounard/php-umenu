<?php

namespace MakinaCorpus\Umenu;

/**
 * Item storage;
 */
interface ItemStorageInterface
{
    /**
     * Append new item within menu
     *
     * @param int $menuId
     * @param int $nodeId
     * @param string $title
     * @param int $weight
     * @param string $description
     *
     * @return int
     */
    public function insert($menuId, $nodeId, $title, $weight = 0, $description = null);

    /**
     * Append new item as child of the selected item
     *
     * @param int $otherItemId
     * @param int $nodeId
     * @param string $title
     * @param int $weight
     * @param string $description
     *
     * @return int
     */
    public function insertAsChild($otherItemId, $nodeId, $title, $weight = 0, $description = null);

    /**
     * Insert item after another
     *
     * @param int $otherItemId
     * @param int $nodeId
     * @param string $title
     * @param int $weight
     * @param string $description
     *
     * @return int
     */
    public function insertAfter($otherItemId, $nodeId, $title, $description = null);

    /**
     * Insert item before another
     *
     * @param int $otherItemId
     * @param int $nodeId
     * @param string $title
     * @param int $weight
     * @param string $description
     *
     * @return int
     */
    public function insertBefore($otherItemId, $nodeId, $title, $description = null);

    /**
     * Update item
     *
     * @param int $itemId
     * @param int $parentId
     * @param string $title
     * @param int $weight
     * @param string $description
     */
    public function update($itemId, $parentId = null, $title = null, $weight = 0, $description = null);

    /**
     * Delete item
     *
     * @param int $itemId
     */
    public function delete($itemId);
}
