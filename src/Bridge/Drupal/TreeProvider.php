<?php

namespace MakinaCorpus\Umenu\Bridge\Drupal;

use MakinaCorpus\Umenu\AbstractTreeProvider;
use MakinaCorpus\Umenu\TreeItem;

/**
 * Default tree provider
 */
class TreeProvider extends AbstractTreeProvider
{
    private $database;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTreeItems($menuId)
    {
        // We need a nice SQL query, that will fetch everything at once.
        $r = $this
            ->database
            ->query(
                "
                    SELECT *, node_id AS page_id
                    FROM {umenu_item}
                    WHERE menu_id = :id
                    ORDER BY
                        weight ASC,
                        parent_id ASC NULLS FIRST,
                        id ASC
                ",
                [':id' => $menuId]
            )
        ;

        $r->setFetchMode(\PDO::FETCH_CLASS, TreeItem::class);

        return $r->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function findAllMenuFor($pageId, array $conditions = [])
    {

        $query = $this
            ->database
            ->select('umenu_item', 'i')
            ->condition('i.node_id', $pageId)
        ;

        $query->addField('i.node_id', 'page_id');
        $query->join('umenu', 'm', "m.id = i.menu_id");
        $query->fields('m', ['id']);
        $query->groupBy('m.id');

        // @todo this is not the right place to do this, but for performances
        //  at the current state, it's the best place to put it: this forces
        //  the findTreeForPage() awaited behavior and always order the menus
        //  using the 'is_main' property DESC to ensure that main menus are
        //  preferred to the others
        $query->orderBy('m.is_main', 'DESC');
        $query->orderBy('m.id', 'ASC');

        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $query->condition('m.' . $key, $value);
            }
        }

        return $query->execute()->fetchCol();
    }

    /**
     * {@inheritdoc}
     */
    protected function ensureAccessOf(array $items, $userId) : array
    {
        $pageMap = [];

        /** @var \MakinaCorpus\Umenu\TreeItem $item */
        foreach ($items as $item) {
            $pageMap[] = $item->getPageId();
        }

        if (!empty($pageMap)) {
            $allowed = $this
                ->database
                ->select('page', 'n')
                ->fields('n', ['nid', 'nid'])
                ->condition('n.nid', $pageMap)
                ->condition('n.status', 1)
                ->addTag('page_access')
                ->execute()
                ->fetchAllKeyed()
            ;

            foreach ($items as $key => $item) {
                if (!isset($allowed[$item->getPageId()])) {
                    unset($items[$key]);
                }
            }
        }

        return $items;
    }
}
