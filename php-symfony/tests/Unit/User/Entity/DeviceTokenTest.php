<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\DeviceToken;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceTokenTest extends TestCase
{
    public function testConstructorInitializesFields(): void
    {
        $token = new DeviceToken('user-123', 'device-token-abc', 'ios');
        $this->assertSame('user-123', $token->getUserId());
        $this->assertSame('device-token-abc', $token->getToken());
        $this->assertSame('ios', $token->getPlatform());
        $this->assertInstanceOf(\DateTime::class, $token->getCreatedAt());
        $this->assertNull($token->getLastUsedAt());
        $this->assertTrue($token->isActive());
        $this->assertIsString($token->getId());
        $this->assertSame(36, strlen($token->getId()));
    }

    public function testGettersSetters(): void
    {
        $token = new DeviceToken('user-1', 'tok', 'web');
        $token->setToken('new-token');
        $this->assertSame('new-token', $token->getToken());

        $lastUsed = new \DateTime('2025-01-01');
        $token->setLastUsedAt($lastUsed);
        $this->assertSame($lastUsed, $token->getLastUsedAt());

        $token->setLastUsedAt(null);
        $this->assertNull($token->getLastUsedAt());

        $token->setIsActive(false);
        $this->assertFalse($token->isActive());
    }

    public function testIdGeneration(): void
    {
        $t1 = new DeviceToken('u', 't', 'android');
        $t2 = new DeviceToken('u', 't', 'android');
        $this->assertNotSame($t1->getId(), $t2->getId());
    }
}
