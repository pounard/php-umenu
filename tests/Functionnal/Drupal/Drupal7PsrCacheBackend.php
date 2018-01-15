<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use Psr\SimpleCache\CacheInterface;

class Drupal7PsrCacheBackend implements \DrupalCacheInterface
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    function get($cid)
    {
        if ($value = $this->cache->get($cid)) {
            return (object)[
                'data' => $value,
            ];
        }
    }

    function getMultiple(&$cids)
    {
        $ret = [];

        foreach ($cids as $index => $cid) {
            if ($entry = $this->get($cid)) {
                $ret[$cid] = $entry;
                unset($cids[$index]);
            }
        }

        return $ret;
    }

    function set($cid, $data, $expire = CACHE_PERMANENT)
    {
        $this->cache->set($cid, $data);
    }

    function clear($cid = NULL, $wildcard = FALSE)
    {
        if ($cid && !$wildcard) {
            $this->cache->delete($cid);
        }
    }

    function isEmpty()
    {
        return false;
    }
}
