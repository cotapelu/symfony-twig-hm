<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use App\Core\Entity\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleTest extends TestCase
{
    private function createMockUser(): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-123');
        return $user;
    }

    public function testConstructorInitializesFields(): void
    {
        $user = $this->createMockUser();
        $role = new UserRole($user, UserRole::ROLE_ADMIN);
        $this->assertIsString($role->getId());
        $this->assertSame($user, $role->getUser());
        $this->assertSame(UserRole::ROLE_ADMIN, $role->getRole());
        $this->assertInstanceOf(\DateTime::class, $role->getGrantedAt());
    }

    public function testGettersSetters(): void
    {
        $user = $this->createMockUser();
        $role = new UserRole($user, UserRole::ROLE_USER);

        // Initial role USER
        $this->assertSame(UserRole::ROLE_USER, $role->getRole());
        $this->assertFalse($role->isAdmin());
        $this->assertFalse($role->isOfficer());
        $this->assertFalse($role->isModerator());

        // Change to ADMIN
        $role->setRole(UserRole::ROLE_ADMIN);
        $this->assertSame(UserRole::ROLE_ADMIN, $role->getRole());
        $this->assertTrue($role->isAdmin());
        $this->assertFalse($role->isOfficer());
        $this->assertFalse($role->isModerator());

        // Change to OFFICER
        $role->setRole(UserRole::ROLE_OFFICER);
        $this->assertSame(UserRole::ROLE_OFFICER, $role->getRole());
        $this->assertTrue($role->isOfficer());
        $this->assertFalse($role->isAdmin());
        $this->assertFalse($role->isModerator());

        // Change to MODERATOR
        $role->setRole(UserRole::ROLE_MODERATOR);
        $this->assertSame(UserRole::ROLE_MODERATOR, $role->getRole());
        $this->assertTrue($role->isModerator());
        $this->assertFalse($role->isAdmin());
        $this->assertFalse($role->isOfficer());
    }

    public function testRoleConstants(): void
    {
        $this->assertSame('admin', UserRole::ROLE_ADMIN);
        $this->assertSame('user', UserRole::ROLE_USER);
        $this->assertSame('officer', UserRole::ROLE_OFFICER);
        $this->assertSame('moderator', UserRole::ROLE_MODERATOR);
    }

    public function testIdSetAndGet(): void
    {
        $user = $this->createMockUser();
        $role = new UserRole($user, UserRole::ROLE_USER);
        $ref = new \ReflectionProperty($role, 'id');
        $ref->setAccessible(true);
        $ref->setValue($role, 'custom-id');
        $this->assertSame('custom-id', $role->getId());
    }
}
