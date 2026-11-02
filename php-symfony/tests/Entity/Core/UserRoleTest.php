<?php declare(strict_types=1);

namespace App\Tests\Entity\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Entity\User;
use App\Core\Entity\UserRole;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testConstructorGeneratesIdAndSetsProperties(): void
    {
        $role = new UserRole($this->user, UserRole::ROLE_ADMIN);
        $this->assertNotEmpty($role->getId());
        $this->assertSame($this->user, $role->getUser());
        $this->assertSame(UserRole::ROLE_ADMIN, $role->getRole());
        $this->assertInstanceOf(\DateTime::class, $role->getGrantedAt());
    }

    public function testSetters(): void
    {
        $role = new UserRole($this->user, UserRole::ROLE_USER);
        $newRole = UserRole::ROLE_OFFICER;
        $role->setRole($newRole);
        $this->assertSame($newRole, $role->getRole());

        $otherUser = new User();
        $role->setUser($otherUser);
        $this->assertSame($otherUser, $role->getUser());
    }
}
