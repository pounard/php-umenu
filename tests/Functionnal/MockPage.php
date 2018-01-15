<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal;

class MockPage
{
    private $id;
    private $title;
    private $siteId;

    public function __construct(int $id, string $title, ?int $siteId = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->siteId = $siteId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getSiteId() : ?int
    {
        return $this->siteId;
    }
}
