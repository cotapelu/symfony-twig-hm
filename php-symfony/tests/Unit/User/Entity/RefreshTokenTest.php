<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\RefreshToken;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RefreshTokenTest extends TestCase
{
    public function testConstructorInitializesFields(): void
    {
        $rt = new RefreshToken('user-123', 'device-123', 'Mozilla/5.0');
        $this->assertSame('user-123', $rt->getUserId());
        $this->assertSame('device-123', $rt->getDeviceId());
        $this->assertSame('Mozilla/5.0', $rt->getUserAgent());
        $this->assertInstanceOf(\DateTime::class, $rt->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $rt->getExpiresAt());
        $this->assertFalse($rt->isRevoked());
        $this->assertIsString($rt->getToken());
        $this->assertIsString($rt->getId());
        $this->assertSame(64, strlen($rt->getId()));
    }

    public function testIsExpired(): void
    {
        $rt = new RefreshToken('u', 'd', null);
        $this->assertFalse($rt->isExpired());
        // set expiresAt to past
        $past = new \DateTime('-1 day');
        $ref = new \ReflectionProperty($rt, 'expiresAt');
        $ref->setAccessible(true);
        $ref->setValue($rt, $past);
        $this->assertTrue($rt->isExpired());
    }

    public function testIsRevoked(): void
    {
        $rt = new RefreshToken('u', 'd', null);
        $this->assertFalse($rt->isRevoked());
        $this->assertFalse($rt->isRevoked());
        $rt->revoke();
        $this->assertTrue($rt->isRevoked());
    }

    public function testIsValid(): void
    {
        $rt = new RefreshToken('u', 'd', null);
        $this->assertTrue($rt->isValid());
        $rt->revoke();
        $this->assertFalse($rt->isValid());
        // unset revoked for next part
        $ref = new \ReflectionProperty($rt, 'revokedAt');
        $ref->setAccessible(true);
        $ref->setValue($rt, null);
        $this->assertTrue($rt->isValid());
        // expire
        $past = new \DateTime('-1 day');
        $ref2 = new \ReflectionProperty($rt, 'expiresAt');
        $ref2->setAccessible(true);
        $ref2->setValue($rt, $past);
        $this->assertFalse($rt->isValid());
    }

    public function testRevokeIdempotent(): void
    {
        $rt = new RefreshToken('u', 'd', null);
        $rt->revoke();
        $this->assertTrue($rt->isRevoked());
        $rt->revoke(); // second revoke still true
        $this->assertTrue($rt->isRevoked());
    }

    public function testTokenValues(): void
    {
        $rt = new RefreshToken('u', 'd', null);
        $token = $rt->getToken();
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
    }
}
