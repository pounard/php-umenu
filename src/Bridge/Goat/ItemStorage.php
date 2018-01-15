<?php

namespace MakinaCorpus\Umenu\Bridge\Goat;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\Umenu\ItemStorageInterface;

/**
 * Item storage using our custom schema
 */
class ItemStorage implements ItemStorageInterface
{
    private $database;

    public function __construct(RunnerInterface $database)
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

        $values = (array)$this
            ->database
            ->query("SELECT id, site_id FROM umenu WHERE id = $*", [$menuId])
            ->fetch()
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
        $values = (array)$this
            ->database
            ->query(
                "SELECT menu_id, site_id, parent_id, weight FROM umenu_item WHERE id = $*",
                [$otherItemId]
            )
            ->fetch()
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

        $exists = (bool)$this->database->query("SELECT 1 FROM umenu_item WHERE id = $*", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        // Find parent identifier
        $data = (array)$this
            ->database
            ->query(
                "SELECT menu_id, site_id, parent_id, weight FROM umenu_item WHERE id = $*",
                [$otherItemId]
            )
            ->fetch()
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
                "SELECT menu_id FROM umenu_item WHERE id = $*",
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
                "SELECT MAX(weight) + 1 FROM umenu_item WHERE menu_id = $* AND parent_id IS NULL",
                [$menuId]
            )
            ->fetchField()
        ;

        return (int)$this
            ->database
            ->insertValues('umenu_item')
            ->values([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'page_id'     => $pageId,
                'parent_id'   => null,
                'weight'      => $weight,
                'title'       => $title,
                'description' => $description,
            ])
            ->returning('id')
            ->execute()
            ->fetchField()
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
                "SELECT MAX(weight) + 1 FROM umenu_item WHERE parent_id = $*",
                [$otherItemId]
            )
            ->fetchField()
        ;

        return (int)$this
            ->database
            ->insertValues('umenu_item')
            ->values([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'page_id'     => $pageId,
                'parent_id'   => $otherItemId,
                'weight'      => $weight,
                'title'       => $title,
                'description' => $description,
            ])
            ->returning('id')
            ->execute()
            ->fetchField()
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
                    "UPDATE umenu_item SET weight = weight + 2 WHERE parent_id = $* AND id <> $* AND weight >= $*",
                    [$parentId, $otherItemId, $weight]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE umenu_item SET weight = weight + 2 WHERE parent_id IS NULL AND id <> $* AND weight >= $*",
                    [$otherItemId, $weight]
                )
            ;
        }

        return (int)$this
            ->database
            ->insertValues('umenu_item')
            ->values([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'page_id'     => $pageId,
                'parent_id'   => $parentId,
                'weight'      => $weight + 1,
                'title'       => $title,
                'description' => $description,
            ])
            ->returning('id')
            ->execute()
            ->fetchField()
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
                    "UPDATE umenu_item SET weight = weight - 2 WHERE parent_id = $* AND id <> $* AND weight <= $*",
                    [$parentId, $otherItemId, $weight]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE umenu_item SET weight = weight - 2 WHERE parent_id IS NULL AND id <> $* AND weight <= $*",
                    [$otherItemId, $weight]
                )
            ;
        }

        return (int)$this
            ->database
            ->insertValues('umenu_item')
            ->values([
                'menu_id'     => $menuId,
                'site_id'     => $siteId,
                'page_id'     => $pageId,
                'parent_id'   => $parentId,
                'weight'      => $weight - 1,
                'title'       => $title,
                'description' => $description,
            ])
            ->returning('id')
            ->execute()
            ->fetchField()
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
                "SELECT 1 FROM umenu_item WHERE id = $*",
                [$itemId]
            )
            ->fetchField()
        ;

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $values = [];
        if (null !== $pageId) {
            $values['page_id'] = $pageId;
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
            ->sets($values)
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

        $this
            ->database
            ->query(
                "
                    WITH max_weight AS (
                        SELECT MAX(weight) + 1 AS weight FROM umenu_item WHERE parent_id = $*
                    )
                    UPDATE umenu_item SET parent_id = $*, weight = (SELECT COALESCE(weight, 0) FROM max_weight) WHERE id = $*
                ",
                [$otherItemId, $otherItemId, $itemId]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot(int $itemId)
    {
        $menuId = $this->getMenuIdFor($itemId);

        $this
            ->database
            ->query(
                "
                    WITH max_weight AS (
                        SELECT MAX(weight) + 1 AS weight FROM umenu_item WHERE (parent_id = 0 OR parent_id IS NULL) AND menu_id = $*
                    )
                    UPDATE umenu_item SET parent_id = NULL, weight = (SELECT COALESCE(weight, 0) FROM max_weight) WHERE id = $*
                ",
                [$menuId, $itemId]
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
                    "UPDATE umenu_item SET weight = weight + 2 WHERE parent_id = $* AND id <> $* AND weight >= $*",
                    [$parentId, $otherItemId, $weight]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE umenu_item SET weight = weight + 2 WHERE parent_id IS NULL AND id <> $* AND weight >= $*",
                    [$otherItemId, $weight]
                )
            ;
        }

        $this
            ->database
            ->query(
                "UPDATE umenu_item SET parent_id = $*, weight = $* WHERE id = $*",
                [$parentId, $weight + 1, $itemId]
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
                    "UPDATE umenu_item SET weight = weight - 2 WHERE parent_id = $* AND id <> $* AND weight <= $*",
                    [$parentId, $otherItemId, $weight - 1]
                )
            ;
        } else {
            $this
                ->database
                ->query(
                    "UPDATE umenu_item SET weight = weight - 2 WHERE parent_id IS NULL AND id <> $* AND weight <= $*",
                    [$otherItemId, $weight - 1]
                )
            ;
        }

        $this
            ->database
            ->query(
                "UPDATE umenu_item SET parent_id = $*, weight = $* WHERE id = $*",
                [$parentId, $weight - 1, $itemId]
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
                "DELETE FROM umenu_item WHERE id = $*",
                [$itemId]
            )
        ;
    }
}
