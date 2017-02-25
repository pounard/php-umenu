<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Loads trees.
 *
 * @todo
 *   This seriously need to be fixed performance-wise
 */
abstract class AbstractTreeProvider implements TreeProviderInterface
{
    private $db;
    private $cache;

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
     * Allow tree cache
     *
     * @param CacheBackendInterface $cache
     */
    public function setCacheBackend(CacheBackendInterface $cache)
    {
        $this->cache = $cache;
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
     * Load tree items
     *
     * @param int $nodeId
     *   Conditions that applies to the menu storage
     * @param mixed[] $conditions
     *   Conditions that applies to the menu storage
     *
     * @return string[]
     *   List of menu identifiers
     */
    abstract protected function findAllMenuFor($nodeId, array $conditions = []);

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
     * {@inheritdoc}
     */
    public function findTreeForNode($nodeId, array $conditions = [])
    {
        $menuIdList = $this->findAllMenuFor($nodeId, $conditions);

        if ($menuIdList) {
            // Arbitrary take the first
            // @todo later give more control to this for users
            return reset($menuIdList);
        }
    }

    /**
     * @inheritdoc
     */
    final public function buildTree($menuId, $withAccess = false, $userId = null, $relocateOrphans = false)
    {
        if ($withAccess && null === $userId) {
            throw new \InvalidArgumentException("loading menu with access checks needs the user identifier");
        }

        $doCache = false;
        $cacheId = null;

        if (!$withAccess) {
            if ($this->cache) {
                $cacheId = 'umenu:tree:' . $menuId;
                $cached = $this->cache->get($cacheId);

                if ($cached && $cached->data instanceof Tree) {
                    return $cached->data;
                }

                $doCache = true;
            }
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

        $tree = new Tree($items, $menuId, $relocateOrphans);

        if ($doCache) {
            $this->cache->set($cacheId, $tree);
        }

        return $tree;
    }
}
