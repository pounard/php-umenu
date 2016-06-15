<?php

namespace MakinaCorpus\Umenu;

/**
 * Uses Drupal core menu_links table to load menu items.
 */
class LegacyTreeProvider extends AbstractTreeProvider
{
    /**
     * Load tree items
     *
     * @param int $menuId
     *
     * @return TreeItem[]
     */
    protected function loadTreeItems($menuId)
    {
        // We need a nice SQL query, that will fetch everything at once.
        $r = $this
            ->getDatabase()
            ->query(
                "
                    SELECT
                        l.mlid
                            AS id,
                        :id1
                            AS menu_id,
                        m.site_id
                            AS site_id,
                        SUBSTRING(l.link_path FROM 6)
                            AS node_id,
                        l.plid
                            AS parent_id,
                        l.weight
                            AS weight,
                        l.link_title
                            AS title,
                        NULL
                            AS description
                    FROM {menu_links} l
                    JOIN {umenu} m ON m.name = l.menu_name
                    WHERE
                        m.id = :id2
                        AND l.link_path LIKE 'node/%'
                    ORDER BY
                        l.weight ASC,
                        l.plid ASC,
                        l.mlid ASC
                ",
                [
                    ':id1' => $menuId,
                    ':id2' => $menuId,
                ]
            )
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, TreeItem::class);

        return $r->fetchAll();
    }
}
