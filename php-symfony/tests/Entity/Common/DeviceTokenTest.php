<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\DeviceToken;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeviceTokenTest extends TestCase
{
    private function createToken(
        string $id = 'token-id',
        string $userId = 'user-1',
        string $token = 'device-token',
        string $platform = 'ios'
    ): DeviceToken {
        return new DeviceToken($id, $userId, $token, $platform);
    }

    public function testConstructor(): void
    {
        $t = $this->createToken();
        $this->assertSame('token-id', $t->getId());
        $this->assertSame('user-1', $t->getUserId());
        $this->assertSame('device-token', $t->getToken());
        $this->assertSame('ios', $t->getPlatform());
        $this->assertNull($t->getDeviceName());
        $this->assertNull($t->getDeviceModel());
        $this->assertNull($t->getOsVersion());
        $this->assertNull($t->getAppVersion());
        $this->assertSame('ACTIVE', $t->getStatus());
        $this->assertNull($t->getLastUsedAt());
        $this->assertInstanceOf(DateTime::class, $t->getCreatedAt());
    }

    public function testSetDeviceName(): void
    {
        $t = $this->createToken();
        $t->setDeviceName('My iPhone');
        $this->assertSame('My iPhone', $t->getDeviceName());
        $t->setDeviceName(null);
        $this->assertNull($t->getDeviceName());
    }

    public function testSetDeviceModel(): void
    {
        $t = $this->createToken();
        $t->setDeviceModel('iPhone 14');
        $this->assertSame('iPhone 14', $t->getDeviceModel());
        $t->setDeviceModel(null);
        $this->assertNull($t->getDeviceModel());
    }

    public function testSetOsVersion(): void
    {
        $t = $this->createToken();
        $t->setOsVersion('17.2');
        $this->assertSame('17.2', $t->getOsVersion());
        $t->setOsVersion(null);
        $this->assertNull($t->getOsVersion());
    }

    public function testSetAppVersion(): void
    {
        $t = $this->createToken();
        $t->setAppVersion('2.5.1');
        $this->assertSame('2.5.1', $t->getAppVersion());
        $t->setAppVersion(null);
        $this->assertNull($t->getAppVersion());
    }

    public function testSetStatus(): void
    {
        $t = $this->createToken();
        $t->setStatus('REVOKED');
        $this->assertSame('REVOKED', $t->getStatus());
        $this->assertFalse($t->isActive());
        $this->assertTrue($t->isRevoked());
        $t->setStatus('ACTIVE');
        $this->assertTrue($t->isActive());
        $this->assertFalse($t->isRevoked());
    }

    public function testSetLastUsedAt(): void
    {
        $t = $this->createToken();
        $now = new DateTime();
        $t->setLastUsedAt($now);
        $this->assertSame($now, $t->getLastUsedAt());
        $t->setLastUsedAt(null);
        $this->assertNull($t->getLastUsedAt());
    }
}
