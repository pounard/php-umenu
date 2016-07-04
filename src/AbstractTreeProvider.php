<?php

namespace MakinaCorpus\Umenu;

/**
 * Loads trees.
 */
abstract class AbstractTreeProvider implements TreeProviderInterface
{
    private $db;

    /**
     * Default constructor, do not ommit it!
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Load tree items
     *
     * @param int $menuId
     *
     * @return TreeItem[]
     */
    abstract protected function loadTreeItems($menuId);

    /**
     * @return \DatabaseConnection
     */
    final protected function getDatabase()
    {
        return $this->db;
    }

    /**
     * @inheritdoc
     */
    public function mayCloneTree()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function cloneTreeIn($menuId, Tree $tree)
    {
        throw new \LogicException("This tree provider implementation cannot clone trees");
    }

    /**
     * @inheritdoc
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false)
    {
        if ($withAccess && null === $userId) {
            throw new \InvalidArgumentException("loading menu with access checks needs the user identifier");
        }

        $items = $this->loadTreeItems($menuId);

        if ($withAccess) {
            $nodeMap = [];

            foreach ($items as $item) {
                $nodeMap[] = $item->getNodeId();
            }

            if (!empty($nodeMap)) {
                $allowed = $this
                    ->getDatabase()
                    ->select('node', 'n')
                    ->fields('n', ['nid', 'nid'])
                    ->condition('n.nid', $nodeMap)
                    ->condition('n.status', 1)
                    ->addTag('node_access')
                    ->execute()
                    ->fetchAllKeyed()
                ;

                foreach ($items as $key => $item) {
                    if (!isset($allowed[$item->getNodeId()])) {
                        unset($items[$key]);
                    }
                }
            }
        }

        return new Tree($items, $menuId, $relocateOrphans);
    }
}
