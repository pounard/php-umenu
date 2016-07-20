<?php

namespace MakinaCorpus\Umenu;

/**
 * Single menu object
 *
 * This class is immutable, modifications are done via the menu storage.
 *
 * Please do not use ArrayAccess methods, they are meant to provide backward
 * compatibility therefore are deprecated.
 */
class Menu implements \ArrayAccess
{
    private $id;
    private $name;
    private $title;
    private $description;
    private $site_id;
    private $is_main;

    public function __construct($id = null, $name = null, $title = null, $description = null, $siteId = null, $isMain = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->is_main = (bool)$isMain;
        $this->title = $title;
        $this->description = $description;
        $this->site_id = $siteId;
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
        return (bool)$this->is_main;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getSiteId()
    {
        return $this->site_id;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function offsetExists($offset)
    {
        trigger_error("MakinaCorpus\Umenu\Menu array access is deprecated, use accessors instead", E_USER_DEPRECATED);

        switch ($offset) {

            case 'id':
            case 'name':
            case 'title':
            case 'description':
            case 'site_id':
                return true;

            default:
                trigger_error(sprintf("MakinaCorpus\Umenu\Menu '%s' property is not mutable, use accessors instead", $offset), E_USER_DEPRECATED);
                break;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function offsetGet($offset)
    {
        trigger_error("MakinaCorpus\Umenu\Menu array access is deprecated, use accessors instead", E_USER_DEPRECATED);

        switch ($offset) {

            case 'id':
                return $this->id;

            case 'name':
                return $this->name;

            case 'title':
                return $this->title;

            case 'description':
                return $this->description;

            case 'site_id':
                return $this->site_id;

            default:
                trigger_error(sprintf("MakinaCorpus\Umenu\Menu '%s' property is not mutable, use accessors instead", $offset), E_USER_DEPRECATED);
                break;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function offsetSet($offset, $value)
    {
        trigger_error("MakinaCorpus\Umenu\Menu is immutable, use accessors instead", E_USER_DEPRECATED);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function offsetUnset($offset)
    {
        trigger_error("MakinaCorpus\Umenu\Menu is immutable, use accessors instead", E_USER_DEPRECATED);
    }
}
