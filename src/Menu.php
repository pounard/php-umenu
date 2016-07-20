<?php

namespace MakinaCorpus\Umenu;

/**
 * Single menu object
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

    public function __construct($id = null, $name = null, $title = null, $description = null, $siteId = null)
    {
        $this->id = $id;
        $this->name = $name;
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

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getSiteId()
    {
        return $this->site_id;
    }

    public function setSiteId($siteId)
    {
        $this->site_id = $siteId;
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
        trigger_error("MakinaCorpus\Umenu\Menu array access is deprecated, use accessors instead", E_USER_DEPRECATED);

        switch ($offset) {

            case 'name':
                $this->name = $value;
                break;

            case 'title':
                $this->title = $value;
                break;

            case 'description':
                $this->description = $value;
                break;

            case 'site_id':
                $this->site_id = $value;
                break;

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
    public function offsetUnset($offset)
    {
        trigger_error("MakinaCorpus\Umenu\Menu array access is deprecated, use accessors instead", E_USER_DEPRECATED);

        switch ($offset) {

            case 'name':
                $this->name = null;
                break;

            case 'title':
                $this->title = null;
                break;

            case 'description':
                $this->description = null;
                break;

            case 'site_id':
                $this->site_id = null;
                break;

            default:
                trigger_error(sprintf("MakinaCorpus\Umenu\Menu '%s' property is not mutable, use accessors instead", $offset), E_USER_DEPRECATED);
                break;
        }
    }
}
