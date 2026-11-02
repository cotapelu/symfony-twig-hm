<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Rate limiting service using Redis-based storage for multi-server deployments.
 */
class RateLimitService
{
    private CacheItemPoolInterface $cache;
    private int $maxRequestsPerMinute;
    private int $maxRequestsPerHour;

    public function __construct(
        CacheItemPoolInterface $rateLimitCache,
        int $maxRequestsPerMinute = 30,
        int $maxRequestsPerHour = 500,
    ) {
        $this->cache = $rateLimitCache;
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;
        $this->maxRequestsPerHour = $maxRequestsPerHour;
    }

    public function checkRateLimit(string $identifier, string $action = 'default'): bool
    {
        $now = time();
        $key = 'rate_limit:'.$identifier.':'.$action;

        // Get existing timestamps from cache
        $timestamps = $this->cache->getItem($key)->get() ?? [];

        // Clean old requests (older than 1 hour)
        $timestamps = array_filter($timestamps, fn ($timestamp) => $timestamp > ($now - 3600));

        // Check minute limit
        $requestsLastMinute = array_filter($timestamps, fn ($timestamp) => $timestamp > ($now - 60));
        if (count($requestsLastMinute) >= $this->maxRequestsPerMinute) {
            return false;
        }

        // Check hour limit
        if (count($timestamps) >= $this->maxRequestsPerHour) {
            return false;
        }

        // Add current request
        $timestamps[] = $now;

        // Limit array size
        if (count($timestamps) > $this->maxRequestsPerHour + 10) {
            $timestamps = array_slice($timestamps, -$this->maxRequestsPerHour);
        }

        // Save back to cache with 1 hour TTL
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set($timestamps);
        $cacheItem->expiresAfter(3600);
        $this->cache->save($cacheItem);

        return true;
    }

    public function getRemainingRequests(string $identifier, string $action = 'default'): array
    {
        $now = time();
        $key = 'rate_limit:'.$identifier.':'.$action;

        $timestamps = $this->cache->getItem($key)->get() ?? [];
        $timestamps = array_filter($timestamps, fn ($timestamp) => $timestamp > ($now - 3600));

        $requestsLastMinute = array_filter($timestamps, fn ($timestamp) => $timestamp > ($now - 60));

        return [
            'minute' => max(0, $this->maxRequestsPerMinute - count($requestsLastMinute)),
            'hour' => max(0, $this->maxRequestsPerHour - count($timestamps)),
        ];
    }

    public function getResetTime(string $identifier, string $action = 'default'): array
    {
        $now = time();
        $key = 'rate_limit:'.$identifier.':'.$action;

        $timestamps = $this->cache->getItem($key)->get() ?? [];

        if (empty($timestamps)) {
            return [
                'minute' => $now + 60,
                'hour' => $now + 3600,
            ];
        }

        $oldestMinute = min(array_filter($timestamps, fn ($timestamp) => $timestamp > ($now - 60)) ?: [$now]);
        $oldestHour = min($timestamps);

        return [
            'minute' => $oldestMinute + 60,
            'hour' => $oldestHour + 3600,
        ];
    }

    public function logRequest(string $identifier, string $action, string $method, bool $success = true): void
    {
        $key = 'rate_limit:'.$identifier.':'.$action;

        $cacheItem = $this->cache->getItem($key);
        $timestamps = $cacheItem->get() ?? [];

        $now = time();

        $timestamps[] = $now;

        $cacheItem->set($timestamps);
        $cacheItem->expiresAfter(3600);
        $this->cache->save($cacheItem);
    }
}
