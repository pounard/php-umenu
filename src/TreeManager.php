<?php

namespace MakinaCorpus\Umenu;

use Drupal\Core\Session\AccountInterface;

class TreeManager
{
    private $storage;
    private $provider;
    private $currentUser;
    private $cache = [];

    public function __construct(MenuStorageInterface $storage, AbstractTreeProvider $provider, AccountInterface $currentUser)
    {
        $this->storage = $storage;
        $this->provider = $provider;
        $this->currentUser = $currentUser;
    }

    /**
     * @return AbstractTreeProvider
     */
    public function getTreeProvider()
    {
        return $this->provider;
    }

    /**
     * @return MenuStorageInterface
     */
    public function getMenuStorage()
    {
        return $this->storage;
    }

    /**
     * Alias of AbstractTreeProvider::buildTree()
     *
     * @param int|string $menuId
     *   Menu name or menu identifier
     * @param boolean $withAccess
     *   If set to true, menu will only container visible items for current user
     *
     * @return \MakinaCorpus\Umenu\Tree
     */
    public function buildTree($menuId, $withAccess = false)
    {
        if (!is_numeric($menuId)) {
            $menuId = $this->storage->load($menuId)['id'];
        }

        if (isset($this->cache[$menuId][(int)$withAccess])) {
            return $this->cache[$menuId][(int)$withAccess];
        }

        return $this->cache[$menuId][(int)$withAccess] = $this
            ->getTreeProvider()
            ->buildTree($menuId, $withAccess, $this->currentUser->id())
        ;
    }
}
