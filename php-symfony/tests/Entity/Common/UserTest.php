<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    private function createUser(
        string $id = 'user-id',
        string $email = 'user@example.com',
        string $passwordHash = 'hashed'
    ): User {
        return new User($id, $email, $passwordHash);
    }

    public function testConstructor(): void
    {
        $u = $this->createUser();
        $this->assertSame('user-id', $u->getId());
        $this->assertSame('user@example.com', $u->getEmail());
        $this->assertNull($u->getPhone());
        $this->assertSame('hashed', $u->getPasswordHash());
        $this->assertSame('active', $u->getStatus());
        $this->assertSame(['ROLE_USER'], $u->getRoles());
        $this->assertNull($u->getProfile());
        $this->assertInstanceOf(\DateTime::class, $u->getCreatedAt());
        $this->assertNull($u->getLastLoginAt());
        $this->assertInstanceOf(\DateTime::class, $u->getUpdatedAt());
        $this->assertTrue($u->isActive());
        $this->assertFalse($u->isSuspended());
        $this->assertFalse($u->isDeleted());
    }

    public function testSetEmail(): void
    {
        $u = $this->createUser();
        $original = $u->getUpdatedAt();
        usleep(1000);
        $u->setEmail('new@example.com');
        $this->assertSame('new@example.com', $u->getEmail());
        $this->assertNotEquals($original, $u->getUpdatedAt());
    }

    public function testSetPhone(): void
    {
        $u = $this->createUser();
        $original = $u->getUpdatedAt();
        usleep(1000);
        $u->setPhone('0123456789');
        $this->assertSame('0123456789', $u->getPhone());
        $this->assertNotEquals($original, $u->getUpdatedAt());
        $u->setPhone(null);
        $this->assertNull($u->getPhone());
    }

    public function testSetPasswordHash(): void
    {
        $u = $this->createUser();
        $original = $u->getUpdatedAt();
        usleep(1000);
        $u->setPasswordHash('newhash');
        $this->assertSame('newhash', $u->getPasswordHash());
        $this->assertNotEquals($original, $u->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $u = $this->createUser();
        $u->setStatus('suspended');
        $this->assertSame('suspended', $u->getStatus());
        $this->assertTrue($u->isSuspended());
        $this->assertFalse($u->isActive());

        $u->setStatus('deleted');
        $this->assertTrue($u->isDeleted());
    }

    public function testSetRoles(): void
    {
        $u = $this->createUser();
        $original = $u->getUpdatedAt();
        usleep(1000);
        $roles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        $u->setRoles($roles);
        $this->assertSame($roles, $u->getRoles());
        $this->assertNotEquals($original, $u->getUpdatedAt());
    }

    public function testSetProfile(): void
    {
        $u = $this->createUser();
        $original = $u->getUpdatedAt();
        usleep(1000);
        $profile = ['name' => 'John', 'age' => 30];
        $u->setProfile($profile);
        $this->assertSame($profile, $u->getProfile());
        $this->assertNotEquals($original, $u->getUpdatedAt());
        $u->setProfile(null);
        $this->assertNull($u->getProfile());
    }

    public function testSetLastLoginAt(): void
    {
        $u = $this->createUser();
        $time = new \DateTime();
        $u->setLastLoginAt($time);
        $this->assertSame($time, $u->getLastLoginAt());
        $u->setLastLoginAt(null);
        $this->assertNull($u->getLastLoginAt());
    }

    public function testStatusHelpers(): void
    {
        $u = $this->createUser();
        $u->setStatus('active');
        $this->assertTrue($u->isActive());
        $u->setStatus('suspended');
        $this->assertTrue($u->isSuspended());
        $u->setStatus('deleted');
        $this->assertTrue($u->isDeleted());
    }
}
