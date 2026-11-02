<?php

declare(strict_types=1);

namespace App\Shared\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheAside
{
    public function __construct(private CacheInterface $cache)
    {}

    /**
     * Get from cache or compute and store.
     *
     * @template T
     */
    public function getOrCompute(string $key, callable $computeFn, int $ttl = 300): mixed
    {
        return $this->cache->get($key, function(ItemInterface $item) use ($computeFn, $ttl) {
            $item->expiresAfter($ttl);
            return $computeFn();
        });
    }

    /**
     * Invalidate a cache entry.
     */
    public function invalidate(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Invalidate by pattern (requires Redis adapter).
     */
    public function invalidatePattern(string $pattern): bool
    {
        if (!$this->cache instanceof \Symfony\Component\Cache\Adapter\RedisAdapter) {
            return false;
        }
        $redis = $this->cache->getRedis();
        $keys = $redis->keys($pattern);
        if ($keys) {
            $redis->del($keys);
        }
        return true;
    }
}
