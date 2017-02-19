<?php

namespace MakinaCorpus\Umenu;

/**
 * Single menu object
 *
 * This class is immutable, modifications are done via the menu storage.
 */
class Menu
{
    private $id;
    private $name;
    private $title;
    private $description;
    private $site_id;
    private $is_main;
    private $role;

    public function __construct($id = null, $name = null, $title = null, $description = null, $siteId = null, $isMain = false, $role = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->is_main = (bool)$isMain;
        $this->title = $title;
        $this->description = $description;
        $this->site_id = $siteId;
        $this->role = $role ? $role : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isSiteMain()
    {
        return $this->hasSiteId() && $this->is_main;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function hasRole()
    {
        return !empty($this->role);
    }

    public function getRole()
    {
        return $this->role;
    }

    public function hasSiteId()
    {
        return !empty($this->site_id);
    }

    public function getSiteId()
    {
        return $this->site_id;
    }
}
