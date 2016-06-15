<?php

namespace MakinaCorpus\Umenu;

/**
 * Et là, on fait rentrer des ronds dans des carrés.
 */
class LegacyItemStorage implements ItemStorageInterface
{
    private $db;

    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    protected function validateMenu($menuId, $title, $nodeId)
    {
        if (empty($menuId)) {
            throw new \InvalidArgumentException("Menu identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($nodeId)) {
            throw new \InvalidArgumentException("Node identifier cannot be empty");
        }

        $menu = $this->db->query("SELECT * FROM {umenu} WHERE id = ?", [$menuId])->fetchAssoc();

        if (!$menu) {
            throw new \InvalidArgumentException(sprintf("Menu %d does not exist", $menuId));
        }

        return $menu;
    }

    protected function validateItem($otherItemId, $title, $nodeId)
    {
        if (empty($otherItemId)) {
            throw new \InvalidArgumentException("Item identifier cannot be empty");
        }
        if (empty($title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }
        if (empty($nodeId)) {
            throw new \InvalidArgumentException("Node identifier cannot be empty");
        }

        // Find parent identifier
        $data = $this
            ->db
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
     * {@inheritdoc}
     */
    public function insert($menuId, $nodeId, $title, $weight = 0, $description = null)
    {
        $menu = $this->validateMenu($menuId, $title, $nodeId);

        $link = [
            'menu_name'  => $menu['name'],
            'link_path'  => 'node/' . $nodeId,
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
    public function insertAsChild($otherItemId, $nodeId, $title, $weight = 0, $description = null)
    {
        list(, $menuName) = $this->validateItem($otherItemId, $title, $nodeId);

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
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
    public function insertAfter($otherItemId, $nodeId, $title, $description = null)
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $nodeId);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} l SET l.weight = l.weight + 2 WHERE l.plid = :plid AND l.mlid <> :mlid AND weight >= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
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
    public function insertBefore($otherItemId, $nodeId, $title, $description = null)
    {
        list(, $menuName, $parentId, $weight) = $this->validateItem($otherItemId, $title, $nodeId);

        $this
            ->db
            ->query(
                "UPDATE {menu_links} l SET l.weight = l.weight - 2 WHERE l.plid = :plid AND l.mlid <> :mlid AND weight <= :neww",
                [
                    ':mlid' => $otherItemId,
                    ':plid' => $parentId,
                    ':neww' => $weight,
                ]
            )
        ;

        $link = [
            'menu_name'  => $menuName,
            'link_path'  => 'node/' . $nodeId,
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
    public function update($itemId, $parentId = null, $title = null, $weight = 0, $description = null)
    {
        $exists = (bool)$this->db->query("SELECT 1 FROM {menu_links} WHERE mlid = ?", [$itemId])->fetchField();

        if (!$exists) {
            throw new \InvalidArgumentException(sprintf("Item %d does not exist", $itemId));
        }

        $item = menu_link_load($itemId);
        $existing = $item;

        if (null !== $parentId) {
            $item['plid'] = $parentId;
        }
        if (null !== $title) {
            $item['link_title'] = $title;
        }
        if (null !== $weight) {
            $item['weight'] = $weight;
        }
        if (null !== $description) {
            $item['description'] = $description;
        }

        if (!menu_link_save($item, $existing)) {
            throw new \RuntimeException("Could not save item");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($itemId)
    {
        menu_link_delete($itemId);
    }
}
