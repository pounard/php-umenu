<?php

namespace MakinaCorpus\Umenu\Bridge\Drupal;

use MakinaCorpus\Umenu\ItemStorageInterface;

/**
 * Et là, on fait rentrer des ronds dans des carrés.
 */
class LegacyItemStorage implements ItemStorageInterface
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

        $menu = $this->database->query("SELECT * FROM {umenu} WHERE id = ?", [$menuId])->fetchAssoc();

        if (!$menu) {
            throw new \InvalidArgumentException(sprintf("Menu %d does not exist", $menuId));
        }

        return $menu;
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
        $data = $this
            ->database
            ->query("
                    SELECT m.id, m.name, l.plid, l.weight
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
                [$otherItemId]
            )
            ->fetchAssoc()
        ;

        if (!$data) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $otherItemId));
        }

        return array_values($data);
    }

    protected function validateMove(int $itemId, int $otherItemId)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Relative item identifier cannot be empty");
        }
        if (empty($itemId)) {
            throw new \InvalidArgumentException("Item identifier cannot be empty");
        }

        $exists = (bool)$this->database->query("SELECT 1 FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        // Find parent identifier
        $data = $this
            ->database
            ->query("
                    SELECT m.id, m.name, l.plid, l.weight
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
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
     * Get menu identifier for item
     *
     * @param int $itemId
     *
     * @return int
     */
    public function getMenuIdFor(int $itemId) : int
    {
        // Find parent identifier
        $menuId = (int)$this
            ->database
            ->query("
                    SELECT m.id
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE l.mlid = ?
                ",
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
        $menu = $this->validateMenu($menuId, $title, $pageId);

        $weight = (int)$this->database->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE menu_name = ? AND plid = 0", [$menu['name']])->fetchField();

        $link = [
            'menu_name'  => $menu['name'],
            'link_path'  => 'page/' . $pageId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAsChild(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list(, $menuName) = $this->validateItem($otherItemId, $title, $pageId);

        $weight = (int)$this->database->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = ?", [$otherItemId])->fetchField();

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'page/' . $pageId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight,
            'plid'       => $otherItemId
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertAfter(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $pageId);

        $this
            ->database
            ->query(
                "UPDATE {menu_links} SET weight = weight + 2 WHERE plid = :plid AND mlid <> :mlid AND weight >= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'page/' . $pageId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight + 1,
            'plid'       => $parentId,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBefore(int $otherItemId, int $pageId, string $title, string $description = '') : int
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $pageId);

        $this
            ->database
            ->query(
                "UPDATE {menu_links} SET weight = weight - 2 WHERE plid = :plid AND mlid <> :mlid AND weight <= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'page/' . $pageId,
            'link_title' => $title,
            'expanded'   => 1,
            'weight'     => $weight - 1,
            'plid'       => $parentId,
        ];

        if (!$id = menu_link_save($link)) {
            throw new \RuntimeException("Could not save item");
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $itemId, int $pageId = null, string $title = '', string $description = '')
    {
        $exists = (bool)$this->database->query("SELECT 1 FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $item = menu_link_load($itemId);

        if (null !== $pageId) {
            $item['link_path'] = 'page/' . $pageId;
        }
        if (null !== $title) {
            $item['link_title'] = $title;
        }
        if (null !== $description) {
            $item['description'] = $description;
        }

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveAsChild(int $itemId, int $otherItemId)
    {
        $this->validateMove($itemId, $otherItemId);

        $weight = (int)$this->database->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = ?", [$otherItemId])->fetchField();

        $item = menu_link_load($itemId);
        $item['weight'] = $weight;
        $item['plid'] = $otherItemId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveToRoot(int $itemId)
    {
        $menuName = (string)$this->database->query("SELECT menu_name FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$menuName) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $weight = (int)$this->database->query("SELECT MAX(weight) + 1 FROM {menu_links} WHERE plid = 0 AND menu_name = ?", [$menuName])->fetchField();

        $item = menu_link_load($itemId);
        $item['weight'] = $weight;
        $item['plid'] = 0;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveAfter(int $itemId, int $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        $this
            ->database
            ->query(
                "UPDATE {menu_links} SET weight = weight + 2 WHERE plid = :plid AND mlid <> :mlid AND weight >= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $item = menu_link_load($itemId);
        $item['weight'] = $weight + 1;
        $item['plid'] = $parentId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveBefore(int $itemId, int $otherItemId)
    {
        list(,, $parentId, $weight) = $this->validateMove($itemId, $otherItemId);

        $this
            ->database
            ->query(
                "UPDATE {menu_links} SET weight = weight - 2 WHERE plid = :plid AND mlid <> :mlid AND weight <= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $item = menu_link_load($itemId);
        $item['weight'] = $weight - 1;
        $item['plid'] = $parentId;

        if (!menu_link_save($item)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $itemId)
    {
        menu_link_delete($itemId);
    }
}
