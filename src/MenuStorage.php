<?php

namespace MakinaCorpus\Umenu;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class MenuStorage implements MenuStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(\DatabaseConnection $db, EventDispatcherInterface $dispatcher = null)
    {
        $this->db = $db;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function load($name)
    {
        if (is_numeric($name)) {
            $list = $this->loadWithConditions(['id' => $name]);
        } else {
            $list = $this->loadMultiple([$name]);
        }

        if (!$list) {
            throw new \InvalidArgumentException(sprintf("%s: menu does not exist", $name));
        }

        return reset($list);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        $list = $this->loadMultiple([$name]);

        return (boolean)$list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultiple($nameList)
    {
        return $this->loadWithConditions(['name' => $nameList]);
        // @todo Sort ?
    }

    /**
     * {@inheritdoc}
     */
    public function loadWithConditions($conditions)
    {
        $q = $this->db->select('umenu', 'um')->fields('um');

        foreach ($conditions as $key => $value) {
            $q->condition('um.' . $key, $value);
        }
        $r = $q->execute();
        $r->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, Menu::class);

        $ret = [];

        while ($menu = $r->fetch()) {
            $ret[$menu->getName()] = $menu;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($name)
    {
        $existing = null;

        if ($this->dispatcher) {
            try {
                $existing = $this->load($name);
            } catch (\InvalidArgumentException $e) {}
        }

        $this->db->delete('umenu')->condition('name', $name)->execute();

        if ($this->dispatcher && $existing) {
            $this->dispatcher->dispatch('menu:delete', new GenericEvent($existing));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($name, array $values)
    {
        unset($values['name'], $values['id'], $values['status']);

        if (empty($values)) { // Nothing to update
            return;
        }

        $this
            ->db
            ->update('umenu')
            ->fields($values)
            ->condition('name', $name)
            ->execute()
        ;

        if ($this->dispatcher) {
            $this->dispatcher->dispatch('menu:update', new GenericEvent($this->load($name)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toggleRole($name, $role)
    {
        $existing = $this->load($name);

        if (!$existing) {
            throw new \InvalidArgumentException(sprintf("%s: cannot change main status, menu does not exist", $name));
        }

        if (!$role) {
            $role = null; // ensure NULL in database and not empty string
        }
        if ($role !== $existing->getRole()) {
            // Nothing to do
            return;
        }

        if ($role) {
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET role = :role WHERE id = :id",
                    [':role' => $role, ':id' => $existing->getId()]
                )
            ;
        } else {
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET role = NULL WHERE id = :id",
                    [':id' => $existing->getId()]
                )
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toggleMainStatus($name, $toggle = true)
    {
        $existing = $this->load($name);

        if (!$existing) {
            throw new \InvalidArgumentException(sprintf("%s: cannot change main status, menu does not exist", $name));
        }
        if (!$existing->getSiteId()) {
            throw new \LogicException(sprintf("%s: cannot change main status, menu does not belong to a site", $name));
        }

        $status = (bool)$toggle;
        if ($status === $existing->isSiteMain()) {
            // Nothing to do
            return;
        }

        if ($status) {
            // Drop main menu status for all others within the same site
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE site_id = :site AND name <> :name",
                    [':site' => $existing->getSiteId(), ':name' => $name]
                )
            ;

            // And change menu, yeah.
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET is_main = 1 WHERE name = :name",
                    [':name' => $name]
                )
            ;
        } else {
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE name = :name",
                    [':name' => $name]
                )
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($name, array $values = [])
    {
        $exists = (bool)$this->db->query("SELECT 1 FROM {umenu} WHERE name = ?", [$name])->fetchField();

        if ($exists) {
            throw new \InvalidArgumentException(sprintf("%s: cannot create menu, already exists", $name));
        }

        $values['name'] = $name;
        if (empty($values['title'])) {
            $values['title'] = $name;
        }

        unset($values['id']);

        $this
            ->db
            ->insert('umenu')
            ->fields($values)
            ->execute()
        ;

        $menu = $this->load($name);

        if ($this->dispatcher) {
            $this->dispatcher->dispatch('menu:create', new GenericEvent($menu));
        }

        return $menu;
    }

    /**
     * {@inheritdoc}
     */
    public function resetCaches()
    {
    }
}
