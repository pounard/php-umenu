<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Psr\SimpleCache\CacheInterface;

class Drupal8PsrCacheBackend implements CacheBackendInterface
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get($cid, $allow_invalid = false)
    {
        if ($value = $this->cache->get($cid)) {
            return (object)[
                'data' => $value,
            ];
        }
    }

    public function getMultiple(&$cids, $allow_invalid = false)
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

    public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = [])
    {
        $this->cache->set($cid, $data);
    }

    public function setMultiple(array $items)
    {
        foreach ($items as $cid => $data) {
            $this->set($cid, $data);
        }
    }

    public function delete($cid)
    {
        $this->cache->delete($cid);
    }

    public function deleteMultiple(array $cids)
    {
        $this->cache->deleteMultiple($cids);
    }

    public function deleteAll()
    {
    }

    public function invalidate($cid)
    {
    }

    public function invalidateMultiple(array $cids)
    {
    }

    public function invalidateAll()
    {
    }

    public function garbageCollection()
    {
    }

    public function removeBin()
    {
    }
}
