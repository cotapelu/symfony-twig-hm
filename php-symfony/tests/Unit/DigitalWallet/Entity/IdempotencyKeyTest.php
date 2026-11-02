<?php declare(strict_types=1);

namespace App\Tests\Unit\DigitalWallet\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Entity\IdempotencyKey;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IdempotencyKeyTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $expires = new \DateTime('+1 hour');
        $key = new IdempotencyKey(
            key: 'key_123',
            entityType: 'transaction',
            entityId: 'txn_456',
            expiresAt: $expires
        );
        $this->assertNull($key->getId());
        $this->assertSame('key_123', $key->getKey());
        $this->assertSame('transaction', $key->getEntityType());
        $this->assertSame('txn_456', $key->getEntityId());
        $this->assertInstanceOf(\DateTime::class, $key->getCreatedAt());
        $this->assertSame($expires, $key->getExpiresAt());
    }

    public function testExpiresAtNullable(): void
    {
        $key = new IdempotencyKey('k', 'type', 'id', null);
        $this->assertNull($key->getExpiresAt());
    }

    public function testIsExpired(): void
    {
        $future = new \DateTime('+1 day');
        $key = new IdempotencyKey('k', 't', 'i', $future);
        $this->assertFalse($key->isExpired());

        $past = new \DateTime('-1 day');
        $key2 = new IdempotencyKey('k2', 't2', 'i2', $past);
        $this->assertTrue($key2->isExpired());

        $key3 = new IdempotencyKey('k3', 't3', 'i3', null);
        $this->assertFalse($key3->isExpired());
    }
}
