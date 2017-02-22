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

    /**
     * Default constructor
     *
     * @param int $id
     * @param string $name
     * @param string $title
     * @param null|string $description
     * @param null|int $siteId
     * @param bool $isMain
     * @param null|string $role
     */
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

    /**
     * Menu menu
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get machine name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Is this site the main menu for site
     *
     * @return bool
     */
    public function isSiteMain()
    {
        return $this->hasSiteId() && $this->is_main;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get description
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Has this menu a role
     *
     * @return bool
     */
    public function hasRole()
    {
        return !empty($this->role);
    }

    /**
     * Get role
     *
     * @return null|string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Has this menu a site identifier
     *
     * @return bool
     */
    public function hasSiteId()
    {
        return !empty($this->site_id);
    }

    /**
     * Get site identifier
     *
     * @return null|int
     */
    public function getSiteId()
    {
        return $this->site_id;
    }
}
