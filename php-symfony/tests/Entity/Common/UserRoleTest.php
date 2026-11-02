<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\UserRole;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleTest extends TestCase
{
    private function createRole(
        string $id = 'role-id',
        string $userId = 'user-1',
        string $role = 'ROLE_USER'
    ): UserRole {
        return new UserRole($id, $userId, $role);
    }

    public function testConstructor(): void
    {
        $r = $this->createRole();
        $this->assertSame('role-id', $r->getId());
        $this->assertSame('user-1', $r->getUserId());
        $this->assertSame('ROLE_USER', $r->getRole());
        $this->assertNull($r->getGrantedBy());
        $this->assertInstanceOf(DateTime::class, $r->getGrantedAt());
        $this->assertNull($r->getExpiresAt());
        $this->assertNull($r->getMetadata());
        $this->assertFalse($r->isExpired());
        $this->assertTrue($r->isActive());
    }

    public function testSetGrantedBy(): void
    {
        $r = $this->createRole();
        $r->setGrantedBy('admin-1');
        $this->assertSame('admin-1', $r->getGrantedBy());
        $r->setGrantedBy(null);
        $this->assertNull($r->getGrantedBy());
    }

    public function testSetGrantedAt(): void
    {
        $r = $this->createRole();
        $original = $r->getGrantedAt();
        usleep(1000);
        $new = new DateTime('-1 day');
        $r->setGrantedAt($new);
        $this->assertSame($new, $r->getGrantedAt());
        $this->assertNotEquals($original, $r->getGrantedAt());
    }

    public function testSetExpiresAt(): void
    {
        $r = $this->createRole();
        $expires = new DateTime('+1 month');
        $r->setExpiresAt($expires);
        $this->assertSame($expires, $r->getExpiresAt());
        $this->assertFalse($r->isExpired());
        $this->assertTrue($r->isActive());

        $past = new DateTime('-1 day');
        $r->setExpiresAt($past);
        $this->assertTrue($r->isExpired());
        $this->assertFalse($r->isActive());

        $r->setExpiresAt(null);
        $this->assertNull($r->getExpiresAt());
        $this->assertFalse($r->isExpired());
        $this->assertTrue($r->isActive());
    }

    public function testSetMetadata(): void
    {
        $r = $this->createRole();
        $meta = ['reason' => 'promotion'];
        $r->setMetadata($meta);
        $this->assertSame($meta, $r->getMetadata());
        $r->setMetadata(null);
        $this->assertNull($r->getMetadata());
    }
}
