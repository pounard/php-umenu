<?php

namespace MakinaCorpus\Umenu\Bridge\Goat;

use Goat\Runner\RunnerInterface;
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
    public function __construct(RunnerInterface $database)
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
                    SELECT *
                    FROM umenu_item
                    WHERE menu_id = $*
                    ORDER BY
                        weight ASC,
                        parent_id ASC NULLS FIRST,
                        id ASC
                ",
                [$menuId],
                TreeItem::class
            )
        ;

        return $r;
    }

    /**
     * {@inheritdoc}
     */
    protected function findAllMenuFor($pageId, array $conditions = [])
    {
        $query = $this
            ->database
            ->select('umenu_item', 'i')
            ->condition('i.page_id', $pageId)
        ;

        $query->join('umenu', 'm', "m.id = i.menu_id");
        $query->column('m.*');
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
}
