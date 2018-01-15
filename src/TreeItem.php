<?php

namespace MakinaCorpus\Umenu;

/**
 * Represents a single tree item.
 *
 * @codeCoverageIgnore
 *   But don't worry, isInTrailOf() will be tested.
 */
final class TreeItem extends TreeBase
{
    private $id;
    private $menu_id;
    private $site_id;
    private $page_id;
    private $parent_id;
    private $weight;
    private $title;
    private $description;

    public function getId()
    {
        return $this->id;
    }

    public function getMenuId()
    {
        return $this->menu_id;
    }

    public function getSiteId()
    {
        return $this->site_id;
    }

    public function getPageId()
    {
        return $this->page_id;
    }

    public function getParentId()
    {
        return $this->parent_id;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getRoute()
    {
        return 'page/' . $this->page_id;
    }

    public function isInTrailOf($pageId)
    {
        if ($pageId === $this->page_id) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->isInTrailOf($pageId)) {
                return true;
            }
        }
    }
}
