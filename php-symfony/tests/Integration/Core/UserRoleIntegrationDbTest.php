<?php

declare(strict_types=1);

namespace App\Tests\Integration\Core;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Core\Entity\User;
use App\Core\Entity\UserRole;

#[AllowMockObjectsWithoutExpectations]
class UserRoleIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private ?User $user = null;

    private function createUser(): User
    {
        $email = 'test-' . bin2hex(random_bytes(8)) . '@example.com';
        $user = new User();
        $user->setEmail($email);
        $user->setPasswordHash(password_hash('password', PASSWORD_BCRYPT));
        // status defaults to active
        $em = self::getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    protected function tearDown(): void
    {
        $em = self::getEntityManager();
        // Remove user roles first
        $roles = $em->getRepository(UserRole::class)->findBy(['user' => $this->user]);
        foreach ($roles as $role) {
            $em->remove($role);
        }
        $em->remove($this->user);
        $em->flush();
        parent::tearDown();
    }

    public function testCreateUserRole(): void
    {
        $role = new UserRole($this->user, UserRole::ROLE_ADMIN);

        $em = self::getEntityManager();
        $em->persist($role);
        $em->flush();

        $this->assertNotNull($role->getId());

        $repo = $em->getRepository(UserRole::class);
        $found = $repo->find($role->getId());

        $this->assertNotNull($found);
        $this->assertSame($this->user->getId(), $found->getUser()->getId());
        $this->assertSame(UserRole::ROLE_ADMIN, $found->getRole());
    }

    public function testFindByUser(): void
    {
        $role1 = new UserRole($this->user, UserRole::ROLE_USER);
        $role2 = new UserRole($this->user, UserRole::ROLE_OFFICER);

        $em = self::getEntityManager();
        $em->persist($role1);
        $em->persist($role2);
        $em->flush();

        $repo = $em->getRepository(UserRole::class);
        $byUser = $repo->findBy(['user' => $this->user->getId()]);

        $this->assertCount(2, $byUser);
        $roles = array_map(fn($r) => $r->getRole(), $byUser);
        $this->assertContains(UserRole::ROLE_USER, $roles);
        $this->assertContains(UserRole::ROLE_OFFICER, $roles);
    }

    public function testDeleteUserRole(): void
    {
        $role = new UserRole($this->user, UserRole::ROLE_MODERATOR);

        $em = self::getEntityManager();
        $em->persist($role);
        $em->flush();

        $id = $role->getId();

        $em->remove($role);
        $em->flush();

        $deleted = $em->getRepository(UserRole::class)->find($id);
        $this->assertNull($deleted);
    }
}
