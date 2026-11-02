<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Infrastructure\RateLimitService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RateLimitServiceTest extends TestCase
{
    public function testConstructor(): void
    {
        // Create a simple mock cache that just stores in memory
        $cache = new class implements \Psr\Cache\CacheItemPoolInterface {
            private array $storage = [];

            public function getItem(string $key): \Psr\Cache\CacheItemInterface
            {
                return new class($key, $this->storage[$key] ?? null) implements \Psr\Cache\CacheItemInterface {
                    private string $key;
                    private mixed $value;
                    private bool $isHit;

                    public function __construct(string $key, mixed $value)
                    {
                        $this->key = $key;
                        $this->value = $value;
                        $this->isHit = $value !== null;
                    }

                    public function getKey(): string { return $this->key; }
                    public function get(): mixed { return $this->value; }
                    public function isHit(): bool { return $this->isHit; }
                    public function set(mixed $value): static { $this->value = $value; return $this; }
                    public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
                    public function expiresAfter(int|\DateInterval|null $time): static { return $this; }
                };
            }

            public function getItems(array $keys = []): iterable { return []; }
            public function hasItem(string $key): bool { return isset($this->storage[$key]); }
            public function clear(): bool { $this->storage = []; return true; }
            public function deleteItem(string $key): bool { unset($this->storage[$key]); return true; }
            public function deleteItems(array $keys): bool { return true; }
            public function save(\Psr\Cache\CacheItemInterface $item): bool {
                $this->storage[$item->getKey()] = $item->get();
                return true;
            }
            public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool { return true; }
            public function commit(): bool { return true; }
        };

        $rateLimitService = new RateLimitService($cache, 5, 10);
        $this->assertInstanceOf(RateLimitService::class, $rateLimitService);
    }

    public function testCheckRateLimitWithinLimits(): void
    {
        $cache = new class implements \Psr\Cache\CacheItemPoolInterface {
            private array $storage = [];

            public function getItem(string $key): \Psr\Cache\CacheItemInterface
            {
                return new class($key, $this->storage[$key] ?? null) implements \Psr\Cache\CacheItemInterface {
                    private string $key;
                    private mixed $value;
                    private bool $isHit;

                    public function __construct(string $key, mixed $value)
                    {
                        $this->key = $key;
                        $this->value = $value;
                        $this->isHit = $value !== null;
                    }

                    public function getKey(): string { return $this->key; }
                    public function get(): mixed { return $this->value; }
                    public function isHit(): bool { return $this->isHit; }
                    public function set(mixed $value): static { $this->value = $value; return $this; }
                    public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
                    public function expiresAfter(int|\DateInterval|null $time): static { return $this; }
                };
            }

            public function getItems(array $keys = []): iterable { return []; }
            public function hasItem(string $key): bool { return isset($this->storage[$key]); }
            public function clear(): bool { $this->storage = []; return true; }
            public function deleteItem(string $key): bool { unset($this->storage[$key]); return true; }
            public function deleteItems(array $keys): bool { return true; }
            public function save(\Psr\Cache\CacheItemInterface $item): bool {
                $this->storage[$item->getKey()] = $item->get();
                return true;
            }
            public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool { return true; }
            public function commit(): bool { return true; }
        };

        $rateLimitService = new RateLimitService($cache, 2, 5); // 2 per min, 5 per hour

        $this->assertTrue($rateLimitService->checkRateLimit('user1', 'login'));
        $this->assertTrue($rateLimitService->checkRateLimit('user1', 'login'));
        $this->assertFalse($rateLimitService->checkRateLimit('user1', 'login')); // Exceeds minute limit
    }

    public function testGetRemainingRequests(): void
    {
        $cache = new class implements \Psr\Cache\CacheItemPoolInterface {
            private array $storage = [];

            public function getItem(string $key): \Psr\Cache\CacheItemInterface
            {
                return new class($key, $this->storage[$key] ?? null) implements \Psr\Cache\CacheItemInterface {
                    private string $key;
                    private mixed $value;
                    private bool $isHit;

                    public function __construct(string $key, mixed $value)
                    {
                        $this->key = $key;
                        $this->value = $value;
                        $this->isHit = $value !== null;
                    }

                    public function getKey(): string { return $this->key; }
                    public function get(): mixed { return $this->value; }
                    public function isHit(): bool { return $this->isHit; }
                    public function set(mixed $value): static { $this->value = $value; return $this; }
                    public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
                    public function expiresAfter(int|\DateInterval|null $time): static { return $this; }
                };
            }

            public function getItems(array $keys = []): iterable { return []; }
            public function hasItem(string $key): bool { return isset($this->storage[$key]); }
            public function clear(): bool { $this->storage = []; return true; }
            public function deleteItem(string $key): bool { unset($this->storage[$key]); return true; }
            public function deleteItems(array $keys): bool { return true; }
            public function save(\Psr\Cache\CacheItemInterface $item): bool {
                $this->storage[$item->getKey()] = $item->get();
                return true;
            }
            public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool { return true; }
            public function commit(): bool { return true; }
        };

        $rateLimitService = new RateLimitService($cache, 3, 10);

        $rateLimitService->checkRateLimit('user1', 'login');
        $remaining = $rateLimitService->getRemainingRequests('user1', 'login');
        $this->assertEquals(2, $remaining['minute']);
        $this->assertEquals(9, $remaining['hour']);
    }
}