<?php

declare(strict_types=1);

namespace App\Shared\Cache;

use Redis;
use RedisException;

/**
 * Product Catalog Cache
 * Caches frequently accessed product data
 */
final class ProductCache
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_TTL_LONG = 3600; // 1 hour

    public function __construct(
        private readonly Redis $redis
    ) {}

    public function getProduct(string $id): ?array
    {
        $key = "product:$id";
        $cached = $this->redis->get($key);
        
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        return null;
    }

    public function setProduct(string $id, array $product): void
    {
        $key = "product:$id";
        $this->redis->setex($key, self::CACHE_TTL, json_encode($product));
    }

    public function getCatalog(int $page = 1, int $limit = 20, ?string $category = null): ?array
    {
        $key = sprintf('catalog:page:%d:limit:%d:cat:%s', $page, $limit, $category ?? 'all');
        $cached = $this->redis->get($key);
        
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        return null;
    }

    public function setCatalog(int $page, int $limit, array $products, ?string $category = null): void
    {
        $key = sprintf('catalog:page:%d:limit:%d:cat:%s', $page, $limit, $category ?? 'all');
        $this->redis->setex($key, self::CACHE_TTL_LONG, json_encode($products));
    }

    public function invalidateProduct(string $id): void
    {
        $this->redis->del(["product:$id"]);
    }

    public function invalidateCatalog(): void
    {
        $keys = $this->redis->keys('catalog:*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    }
}

/**
 * User Profile Cache
 * Caches user profile data
 */
final class UserProfileCache
{
    private const CACHE_TTL = 600; // 10 minutes

    public function __construct(
        private readonly Redis $redis
    ) {}

    public function getUser(string $id): ?array
    {
        $key = "user:$id";
        $cached = $this->redis->get($key);
        
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        return null;
    }

    public function setUser(string $id, array $user): void
    {
        $key = "user:$id";
        $this->redis->setex($key, self::CACHE_TTL, json_encode($user));
    }

    public function invalidateUser(string $id): void
    {
        $this->redis->del(["user:$id"]);
    }
}

/**
 * Wallet Balance Cache
 * Caches wallet balance for quick access
 */
final class WalletCache
{
    private const CACHE_TTL = 60; // 1 minute (short due to financial data)

    public function __construct(
        private readonly Redis $redis
    ) {}

    public function getBalance(string $userId): ?array
    {
        $key = "wallet:balance:$userId";
        $cached = $this->redis->get($key);
        
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        return null;
    }

    public function setBalance(string $userId, array $balance): void
    {
        $key = "wallet:balance:$userId";
        $this->redis->setex($key, self::CACHE_TTL, json_encode($balance));
    }

    public function invalidateBalance(string $userId): void
    {
        $this->redis->del(["wallet:balance:$userId"]);
    }
}