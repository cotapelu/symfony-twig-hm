<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\UserSession;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionTest extends TestCase
{
    private function createSession(
        string $id = 'session-id',
        string $userId = 'user-1',
        string $tokenHash = 'tokenhash',
        ?\DateTime $expiresAt = null
    ): UserSession {
        $expiresAt ??= new \DateTime('+1 hour');
        return new UserSession($id, $userId, $tokenHash, $expiresAt);
    }

    public function testConstructor(): void
    {
        $expires = new \DateTime('+1 year');
        $s = $this->createSession('session-1', 'user-1', 'hash123', $expires);
        $this->assertSame('session-1', $s->getId());
        $this->assertSame('user-1', $s->getUserId());
        $this->assertSame('hash123', $s->getTokenHash());
        $this->assertNull($s->getIpAddress());
        $this->assertNull($s->getUserAgent());
        $this->assertNull($s->getDeviceInfo());
        $this->assertSame($expires, $s->getExpiresAt());
        $this->assertNull($s->getLastActivityAt());
        $this->assertSame('ACTIVE', $s->getStatus());
        $this->assertInstanceOf(\DateTime::class, $s->getCreatedAt());
        // Not expired if in future
        $this->assertFalse($s->isExpired());
        $this->assertTrue($s->isActive());
        $this->assertFalse($s->isRevoked());
    }

    public function testSetIpAddress(): void
    {
        $s = $this->createSession();
        $s->setIpAddress('127.0.0.1');
        $this->assertSame('127.0.0.1', $s->getIpAddress());
        $s->setIpAddress(null);
        $this->assertNull($s->getIpAddress());
    }

    public function testSetUserAgent(): void
    {
        $s = $this->createSession();
        $s->setUserAgent('Mozilla/5.0');
        $this->assertSame('Mozilla/5.0', $s->getUserAgent());
        $s->setUserAgent(null);
        $this->assertNull($s->getUserAgent());
    }

    public function testSetDeviceInfo(): void
    {
        $s = $this->createSession();
        $info = ['os' => 'Linux', 'browser' => 'Chrome'];
        $s->setDeviceInfo($info);
        $this->assertSame($info, $s->getDeviceInfo());
        $s->setDeviceInfo(null);
        $this->assertNull($s->getDeviceInfo());
    }

    public function testSetExpiresAt(): void
    {
        $s = $this->createSession();
        $new = new \DateTime('+2 days');
        $s->setExpiresAt($new);
        $this->assertSame($new, $s->getExpiresAt());
    }

    public function testSetLastActivityAt(): void
    {
        $s = $this->createSession();
        $now = new \DateTime();
        $s->setLastActivityAt($now);
        $this->assertSame($now, $s->getLastActivityAt());
        $s->setLastActivityAt(null);
        $this->assertNull($s->getLastActivityAt());
    }

    public function testSetStatus(): void
    {
        $s = $this->createSession();
        $s->setStatus('REVOKED');
        $this->assertSame('REVOKED', $s->getStatus());
        $this->assertTrue($s->isRevoked());
        $this->assertFalse($s->isActive());

        $s->setStatus('ACTIVE');
        $this->assertFalse($s->isRevoked());
        $this->assertTrue($s->isActive());
    }

    public function testIsExpiredAndActiveWithPastExpiry(): void
    {
        $past = new \DateTime('-1 hour');
        $s = $this->createSession();
        $s->setExpiresAt($past);
        $this->assertTrue($s->isExpired());
        // isActive requires not expired; even if status ACTIVE, expiry makes inactive
        $this->assertFalse($s->isActive());
    }

    public function testIsActiveWhenRevoked(): void
    {
        $s = $this->createSession();
        $s->setStatus('REVOKED');
        $this->assertTrue($s->isRevoked());
        $this->assertFalse($s->isActive());
    }
}
