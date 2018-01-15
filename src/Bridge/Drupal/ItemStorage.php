<?php

namespace MakinaCorpus\Umenu\Bridge\Drupal;

use MakinaCorpus\Umenu\ItemStorageInterface;

/**
 * Item storage using our custom schema
 */
class ItemStorage implements ItemStorageInterface
{
    private $database;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    protected function validateMenu(int $menuId, string $title, int $pageId)
    {
        if (empty($menuId)) {
            throw new \InvalidArgumentException("Menu identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($pageId)) {
            throw new \InvalidArgumentException("Page identifier cannot be empty");
        }

        $values = $this
            ->database
            ->query("SELECT id, site_id FROM {umenu} WHERE id = ?", [$menuId])
            ->fetchAssoc()
        ;

        if (!$values) {
            throw new \InvalidArgumentException(sprintf("Menu %d does not exist", $menuId));
        }

        return array_values($values);
    }

    protected function validateItem(int $otherItemId, string $title, int $pageId)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Relative item identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($pageId)) {
            throw new \InvalidArgumentException("Page identifier cannot be empty");
        }

        // Find parent identifier
        $values = $this
            ->database
            ->query(
                "SELECT menu_id, site_id, parent_id, weight FROM {umenu_item} WHERE id = ?",
                [$otherItemId]
            )
            ->fetchAssoc()
        ;

        if (!$values) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $otherItemId));
        }

        return array_values($values);
    }

    protected function validateMove(int $itemId, int $otherItemId)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Relative item identifier cannot be empty");
        }
        if (empty($itemId)) {
            throw new \InvalidArgumentException("Item identifier cannot be empty");
        }

        $exists = (bool)$this->database->query("SELECT 1 FROM {umenu_item} WHERE id = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        // Find parent identifier
        $data = $this
            ->database
            ->query(
                "SELECT menu_id, site_id, parent_id, weight FROM {umenu_item} WHERE id = ?",
                [$otherItemId]
            )
            ->fetchAssoc()
        ;

        if (!$data) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $otherItemId));
        }

        return array_values($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuIdFor(int $itemId) : int
    {
        // Find parent identifier
        $menuId = (int)$this
            ->database
            ->query(
                "SELECT menu_id FROM {umenu_item} WHERE id = ?",
                [$itemId]
            )
            ->fetchField()
        ;

        if (!$menuId) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        return $menuId;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(int $menuId, int $pageId, string $title, string $description = '') : int
    {
        list($menuId, $siteId) = $this->validateMenu($menuId, $title, $pageId);

        $weight = (int)$this
            ->database
            ->query(
                "SELECT MAX(weight) + 1 FROM {umenu_item} WHERE menu_id = ? AND parent_id IS NULL",
                [$menuId]
            )
            ->fetchField()
        ;

        return (int)$this
            ->database
            ->insert('umenu_item')
            ->fields([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'node_id'     => $pageId,
                'parent_id'   => null,
                'weight'      => $weight,
                'title'       => $title,
                'description' => $description,
            ])
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list($menuId, $siteId) = $this->validateItem($otherItemId, $title, $pageId);

        $weight = (int)$this
            ->database
            ->query(
                "SELECT MAX(weight) + 1 FROM {umenu_item} WHERE parent_id = ?",
                [$otherItemId]
            )
            ->fetchField()
        ;

        return (int)$this
            ->database
            ->insert('umenu_item')
            ->fields([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'node_id'     => $pageId,
                'parent_id'   => $otherItemId,
                'weight'      => $weight,
                'title'       => $title,
                'description' => $description,
            ])
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list($menuId, $siteId, $parentId, $weight) = $this->validateItem($otherItemId, $title, $pageId);

        if ($parentId) {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight + 2 WHERE parent_id = :parent AND id <> :id AND weight >= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':parent' => $parentId,
                        ':weight' => $weight,
                    ]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight + 2 WHERE parent_id IS NULL AND id <> :id AND weight >= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':weight' => $weight,
                    ]
                )
            ;
        }

        return (int)$this
            ->database
            ->insert('umenu_item')
            ->fields([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'node_id'     => $pageId,
                'parent_id'   => $parentId,
                'weight'      => $weight + 1,
                'title'       => $title,
                'description' => $description,
            ])
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list($menuId, $siteId, $parentId, $weight) = $this->validateItem($otherItemId, $title, $pageId);

        if ($parentId) {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight - 2 WHERE parent_id = :parent AND id <> :id AND weight <= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':parent' => $parentId,
                        ':weight' => $weight,
                    ]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight - 2 WHERE parent_id IS NULL AND id <> :id AND weight <= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':weight' => $weight,
                    ]
                )
            ;
        }

        return (int)$this
            ->database
            ->insert('umenu_item')
            ->fields([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'node_id'     => $pageId,
                'parent_id'   => $parentId,
                'weight'      => $weight - 1,
                'title'       => $title,
                'description' => $description,
            ])
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $itemId, int $pageId = null, string $title = '', string $description = '')
    {
        $exists = (bool)$this
            ->database
            ->query(
                "SELECT 1 FROM {umenu_item} WHERE id = ?",
                [$itemId]
            )
            ->fetchField()
        ;

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $values = [];
        if (null !== $pageId) {
            $values['node_id'] = $pageId;
        }
        if (null !== $title) {
            $values['title'] = $title;
        }
        if (null !== $description) {
            $values['description'] = $description;
        }

        if (empty($values)) {
            return;
        }

        $this
            ->database
            ->update('umenu_item')
            ->fields($values)
            ->condition('id', $itemId)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild(int $itemId, int $otherItemId)
    {
        $this->validateMove($itemId, $otherItemId);

        $weight = (int)$this
            ->database
            ->query(
                "SELECT MAX(weight) + 1 FROM {umenu_item} WHERE parent_id = ?",
                [$otherItemId]
            )
            ->fetchField()
        ;

        $this
            ->database
            ->query(
                "UPDATE {umenu_item} SET parent_id = :parent, weight = :weight WHERE id = :id",
                [
                    ':id'     => $itemId,
                    ':parent' => $otherItemId,
                    ':weight' => $weight,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot(int $itemId)
    {
        $menuId = $this->getMenuIdFor($itemId);

        $weight = (int)$this
            ->database
            ->query(
                "SELECT MAX(weight) + 1 FROM {umenu_item} WHERE (parent_id = 0 OR parent_id IS NULL) AND menu_id = ?",
                [$menuId]
            )
            ->fetchField()
        ;

        $this
            ->database
            ->query(
                "UPDATE {umenu_item} SET parent_id = NULL, weight = :weight WHERE id = :id",
                [
                    ':id'     => $itemId,
                    ':weight' => $weight,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter(int $itemId, int $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        if ($parentId) {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight + 2 WHERE parent_id = :parent AND id <> :id AND weight >= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':parent' => $parentId,
                        ':weight' => $weight,
                    ]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight + 2 WHERE parent_id IS NULL AND id <> :id AND weight >= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':weight' => $weight,
                    ]
                )
            ;
        }

        $this
            ->database
            ->query(
                "UPDATE {umenu_item} SET parent_id = :parent, weight = :weight WHERE id = :id",
                [
                    ':id'     => $itemId,
                    ':parent' => $parentId,
                    ':weight' => $weight + 1,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore(int $itemId, int $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        if ($parentId) {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight - 2 WHERE parent_id = :parent AND id <> :id AND weight <= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':parent' => $parentId,
                        ':weight' => $weight - 1,
                    ]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE {umenu_item} SET weight = weight - 2 WHERE parent_id IS NULL AND id <> :id AND weight <= :weight",
                    [
                        ':id'     => $otherItemId,
                        ':weight' => $weight - 1,
                    ]
                )
            ;
        }

        $this
            ->database
            ->query(
                "UPDATE {umenu_item} SET parent_id = :parent, weight = :weight WHERE id = :id",
                [
                    ':id'     => $itemId,
                    ':parent' => $parentId,
                    ':weight' => $weight - 1,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $itemId)
    {
        $this
            ->database
            ->query(
                "DELETE FROM {umenu_item} WHERE id = ?",
                [$itemId]
            )
        ;
    }
}
