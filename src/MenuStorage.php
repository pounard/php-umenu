<?php

namespace MakinaCorpus\Umenu;

use MakinaCorpus\Umenu\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            $this->dispatcher->dispatch(MenuEvent::EVENT_DELETE, new MenuEvent($existing));
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
            $this->dispatcher->dispatch(MenuEvent::EVENT_UPDATE, new MenuEvent($this->load($name)));
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

        if ($this->dispatcher && $existing) {
            $this->dispatcher->dispatch(MenuEvent::EVENT_TOGGLE_ROLE, new MenuEvent($existing, ['role' => $role]));
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
            if ($existing->getSiteId()) {
                $this->db->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE site_id = :site AND id <> :id",
                    [':site' => $existing->getSiteId(), ':id' => $existing->getId()]
                );
            } else {
                $this->db->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE site_id IS NULL OR site_id = 0 AND id <> :id",
                    [':site' => $existing->getSiteId(), ':id' => $existing->getId()]
                );
            }

            // And change menu, yeah.
            $this->db->query("UPDATE {umenu} SET is_main = 1 WHERE id = :id",[':id' => $existing->getId()]);

        } else {
            $this
                ->db
                ->query(
                    "UPDATE {umenu} SET is_main = 0 WHERE id = :id",
                    [':name' => $existing->getId()]
                )
            ;
        }

        if ($this->dispatcher && $existing) {
            $this->dispatcher->dispatch(MenuEvent::EVENT_TOGGLE_MAIN, new MenuEvent($existing, ['is_main' => $toggle]));
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
            $this->dispatcher->dispatch(MenuEvent::EVENT_CREATE, new MenuEvent($menu));
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
