<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal;

use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\CachedItemStorageProxy;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ArrayCache;

trait MenuTestTrait
{
    private $pageIdSeq = 1;
    private $siteIdSeq = 1;
    private $cacheBackend;

    /**
     * Create the tested item storage
     */
    abstract protected function getItemStorage() : ItemStorageInterface;

    /**
     * Create the tested menu storage
     */
    abstract protected function getMenuStorage() : MenuStorageInterface;

    /**
     * Create the tested tree provider
     */
    abstract protected function getTreeProvider() : TreeProviderInterface;

    /**
     * Create a cached item storage
     */
    protected function getCacheAwareItemStorage() : ItemStorageInterface
    {
        return new CachedItemStorageProxy(
            $this->getItemStorage(),
            $this->getCacheBackend()
        );
    }

    /**
     * Create cache backennd
     */
    protected function getCacheBackend() : CacheInterface
    {
        if (!$this->cacheBackend) {
            $this->cacheBackend = new ArrayCache();
        }

        return $this->cacheBackend;
    }

    /**
     * Create a site and return its identifier
     *
     * Site is a heritage from the legacy Drupal code.
     */
    protected function createSite() : int
    {
        return $this->siteIdSeq++;
    }

    /**
     * Create a page and return a mock object for testing
     *
     * Site is a heritage from the legacy Drupal code.
     */
    protected function createPage(string $title, ?int $siteId = null) : MockPage
    {
        return new MockPage($this->pageIdSeq++, $title, $siteId);
    }
}
