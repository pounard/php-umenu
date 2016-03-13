<?php
namespace MakinaCorpus\Umenu;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class DrupalMenuStorage implements MenuStorageInterface
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
        $list = $this->loadMultiple([$name]);

        if (!$list) {
            throw new \InvalidArgumentException(sprintf("%s: menu does not exist", $name));
        }

        return reset($list);
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

        $ret = [];
        while ($menu = $r->fetchAssoc()) {
            $ret[$menu['name']] = $menu;
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
            $this->dispatcher->dispatch('menu:create', new GenericEvent($existing));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($name, array $values)
    {
        $existing = $this->load($name);

        if (array_key_exists('name', $values)) {
            unset($values['name']);
        }

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
            $existing = $values + $existing;
            $this->dispatcher->dispatch('menu:update', new GenericEvent($existing));
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