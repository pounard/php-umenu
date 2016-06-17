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
     * Can this implementation clone trees
     *
     * @return boolean
     */
    public function mayCloneTree()
    {
        return false;
    }

    /**
     * Clone full tree in given menu
     *
     * @param int $menuId
     * @param Tree $tree
     *
     * @return Tree
     *   Newly created tree
     */
    public function cloneTreeIn($menuId, Tree $tree)
    {
        throw new \LogicException("This tree provider implementation cannot clone trees");
    }

    /**
     * Build tree
     *
     * @param int $menuId
     *   Menu identifier
     * @param boolean $withAccess
     *   Should this check access when loading tree
     * @param int $userId
     *   User account identifier for access checks
     * @return \MakinaCorpus\Umenu\Tree
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null)
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

        return new Tree($items, $menuId);
    }
}
