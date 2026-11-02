<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\UserRole;
use App\Service\Common\UserRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserRoleServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private UserRoleService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new UserRoleService($this->em, $this->logger);
    }

    public function testGrantRole(): void
    {
        $userId = 'user_123';
        $role = UserRoleService::ROLE_CITIZEN;
        $now = new \DateTime();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserRole::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (UserRole $r) use ($userId, $role, $now) {
                return $r->getUserId() === $userId
                    && $r->getRole() === $role
                    && $r->isActive();
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User role granted', $this->anything());

        $result = $this->service->grantRole($userId, $role);
        $this->assertInstanceOf(UserRole::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($role, $result->getRole());
    }

    public function testGrantRoleAlreadyExists(): void
    {
        $userId = 'user_123';
        $role = UserRoleService::ROLE_CITIZEN;
        $existing = new UserRole($this->generateIdStub(), $userId, $role);
        $existing->setExpiresAt(null); // ensure not expired

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->never())
            ->method('persist');
        $result = $this->service->grantRole($userId, $role);
        $this->assertSame($existing, $result);
    }

    public function testRevokeRole(): void
    {
        $userId = 'user_123';
        $role = UserRoleService::ROLE_CITIZEN;
        $userRole = new UserRole($this->generateIdStub(), $userId, $role);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($userRole);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($userRole);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->revokeRole($userId, $role);
        $this->assertTrue($result);
    }

    public function testRevokeRoleNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->revokeRole('user', 'role');
        $this->assertFalse($result);
    }

    public function testGetUserRoles(): void
    {
        $userId = 'user_123';
        $roles = [
            new UserRole('id1', $userId, UserRoleService::ROLE_CITIZEN),
            new UserRole('id2', $userId, UserRoleService::ROLE_DRIVER),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($roles);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserRoles($userId);
        $this->assertSame($roles, $result);
    }

    public function testGetActiveUserRoles(): void
    {
        $userId = 'user_123';
        $active = new UserRole('id1', $userId, UserRoleService::ROLE_CITIZEN);
        $active->setGrantedAt(new \DateTime());
        $expired = new UserRole('id2', $userId, UserRoleService::ROLE_DRIVER);
        $expired->setExpiresAt((new \DateTime())->modify('-1 day'));

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn([$active, $expired]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveUserRoles($userId);
        $this->assertCount(1, $result);
        $this->assertSame($active, $result[0]);
    }

    public function testHasRole(): void
    {
        $userId = 'user_123';
        $role = UserRoleService::ROLE_CITIZEN;
        $userRole = new UserRole('id', $userId, $role);
        $userRole->setGrantedAt(new \DateTime());

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($userRole);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->hasRole($userId, $role);
        $this->assertTrue($result);
    }

    public function testHasRoleInactive(): void
    {
        $userId = 'user_123';
        $role = UserRoleService::ROLE_CITIZEN;
        $userRole = new UserRole('id', $userId, $role);
        $userRole->setExpiresAt((new \DateTime())->modify('-1 day')); // expired

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($userRole);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->hasRole($userId, $role);
        $this->assertFalse($result);
    }

    public function testHasAnyRole(): void
    {
        $userId = 'user_123';
        $roles = [UserRoleService::ROLE_CITIZEN, UserRoleService::ROLE_DRIVER];
        $userRole = new UserRole('id', $userId, $roles[0]);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $roles[0]])
            ->willReturn($userRole);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->hasAnyRole($userId, $roles);
        $this->assertTrue($result);
    }

    public function testRevokeAllRoles(): void
    {
        $userId = 'user_123';
        $roles = [
            new UserRole('id1', $userId, UserRoleService::ROLE_CITIZEN),
            new UserRole('id2', $userId, UserRoleService::ROLE_DRIVER),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($roles);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        foreach ($roles as $role) {
            $this->em->expects($this->once())
                ->method('remove')
                ->with($role);
        }
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->revokeAllRoles($userId);
        $this->assertSame(2, $result);
    }

    public function testGetUsersByRole(): void
    {
        $role = UserRoleService::ROLE_CITIZEN;
        $userRoles = [
            new UserRole('id1', 'user1', $role),
            new UserRole('id2', 'user2', $role),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['role' => $role])
            ->willReturn($userRoles);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUsersByRole($role);
        $this->assertSame($userRoles, $result);
    }

    public function testCleanupExpiredRoles(): void
    {
        $now = new \DateTime();
        $expired1 = new UserRole('id1', 'user1', 'role');
        $expired1->setExpiresAt((new \DateTime())->modify('-2 days'));
        $expired2 = new UserRole('id2', 'user2', 'role');
        $expired2->setExpiresAt((new \DateTime())->modify('-1 day'));

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('r.expiresAt < :now')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('now', $now)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$expired1, $expired2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(UserRole::class)
            ->willReturn($repo);
        foreach ([$expired1, $expired2] as $expired) {
            $this->em->expects($this->once())
                ->method('remove')
                ->with($expired);
        }
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cleanupExpiredRoles();
        $this->assertSame(2, $result);
    }

    public function testGetAvailableRoles(): void
    {
        $roles = $this->service->getAvailableRoles();
        $this->assertContains(UserRoleService::ROLE_ADMIN, $roles);
        $this->assertContains(UserRoleService::ROLE_OFFICER, $roles);
        $this->assertContains(UserRoleService::ROLE_CITIZEN, $roles);
        $this->assertContains(UserRoleService::ROLE_DRIVER, $roles);
        $this->assertContains(UserRoleService::ROLE_MERCHANT, $roles);
        $this->assertContains(UserRoleService::ROLE_USER, $roles);
        $this->assertCount(6, $roles);
    }

    public function testIsValidRole(): void
    {
        $this->assertTrue($this->service->isValidRole(UserRoleService::ROLE_ADMIN));
        $this->assertTrue($this->service->isValidRole(UserRoleService::ROLE_CITIZEN));
        $this->assertFalse($this->service->isValidRole('invalid_role'));
    }

    /**
     * Helper to generate a stub ID for existing roles.
     */
    private function generateIdStub(): string
    {
        return 'role_123';
    }
}
