<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\UserRole;
use App\Repository\Common\UserRoleRepository;
use App\Service\Common\UserRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class UserRoleServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRoleRepository $repository;
    private LoggerInterface $logger;
    private UserRoleService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRoleRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->with(UserRole::class)
            ->willReturn($this->repository);
    }

    public function testGrantRoleNew(): void
    {
        $userId = 'user-1';
        $role = UserRoleService::ROLE_ADMIN;
        $generatedId = 'generated-id';

        // Repository finds no existing
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn(null);

        // Capture persisted entity
        $persisted = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (UserRole $ur) use (&$persisted, $userId, $role) {
                $persisted = $ur;
                return $persisted !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User role granted', $this->arrayHasKey('user_id'));

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->grantRole($userId, $role);

        $this->assertInstanceOf(UserRole::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($role, $result->getRole());
    }

    public function testGrantRoleExistingNotExpiredReturnsExisting(): void
    {
        $userId = 'user-2';
        $role = UserRoleService::ROLE_USER;
        $existing = $this->createMock(UserRole::class);
        $existing->method('isExpired')->willReturn(false);
        // grantRole returns $existing when not null and not expired

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($existing);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->grantRole($userId, $role);
        $this->assertSame($existing, $result);
    }

    public function testGrantRoleExistingExpiredUpdates(): void
    {
        $userId = 'user-3';
        $role = UserRoleService::ROLE_CITIZEN;
        $grantedBy = 'admin-1';
        $expiresAt = new \DateTime('+1 year');
        $metadata = ['reason' => 'renewal'];

        $existing = $this->createMock(UserRole::class);
        $existing->method('isExpired')->willReturn(true);
        $existing->expects($this->once())->method('setGrantedBy')->with($grantedBy);
        $existing->expects($this->once())->method('setGrantedAt')->with($this->isInstanceOf(\DateTime::class));
        $existing->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $existing->expects($this->once())->method('setMetadata')->with($metadata);
        //getId for log
        $existing->method('getId')->willReturn('role-id-123');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($existing);
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('User role re-granted', $this->arrayHasKey('user_id'));

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->grantRole($userId, $role, $grantedBy, $expiresAt, $metadata);
        $this->assertSame($existing, $result);
    }

    public function testRevokeRoleSuccess(): void
    {
        $userId = 'user-4';
        $role = UserRoleService::ROLE_DRIVER;
        $userRole = $this->createMock(UserRole::class);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($userRole);
        $this->em->expects($this->once())->method('remove')->with($userRole);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('User role revoked', ['user_id' => $userId, 'role' => $role]);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->revokeRole($userId, $role);
        $this->assertTrue($result);
    }

    public function testRevokeRoleNotFound(): void
    {
        $userId = 'user-5';
        $role = UserRoleService::ROLE_MERCHANT;

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn(null);
        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->revokeRole($userId, $role);
        $this->assertFalse($result);
    }

    public function testGetRole(): void
    {
        $id = 'role-id';
        $role = $this->createMock(UserRole::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($role);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->getRole($id);
        $this->assertSame($role, $result);
    }

    public function testGetUserRoles(): void
    {
        $userId = 'user-6';
        $roles = [$this->createMock(UserRole::class), $this->createMock(UserRole::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($roles);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->getUserRoles($userId);
        $this->assertSame($roles, $result);
    }

    public function testGetActiveUserRolesFiltersExpired(): void
    {
        $userId = 'user-7';
        $active = $this->createMock(UserRole::class);
        $active->method('isActive')->willReturn(true);
        $expired = $this->createMock(UserRole::class);
        $expired->method('isActive')->willReturn(false);
        $all = [$active, $expired];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($all);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->getActiveUserRoles($userId);
        $this->assertCount(1, $result);
        $this->assertSame($active, $result[0]);
    }

    public function testHasRoleWhenActive(): void
    {
        $userId = 'user-8';
        $role = UserRoleService::ROLE_OFFICER;
        $userRole = $this->createMock(UserRole::class);
        $userRole->method('isActive')->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($userRole);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->hasRole($userId, $role);
        $this->assertTrue($result);
    }

    public function testHasRoleWhenInactive(): void
    {
        $userId = 'user-9';
        $role = UserRoleService::ROLE_USER;
        $userRole = $this->createMock(UserRole::class);
        $userRole->method('isActive')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($userRole);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->hasRole($userId, $role);
        $this->assertFalse($result);
    }

    public function testHasRoleWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->hasRole('user-10', UserRoleService::ROLE_DRIVER);
        $this->assertFalse($result);
    }

    public function testHasAnyRoleWhenOneMatches(): void
    {
        $userId = 'user-11';
        $roles = [UserRoleService::ROLE_USER, UserRoleService::ROLE_ADMIN];
        $inactive = $this->createMock(UserRole::class);
        $inactive->method('isActive')->willReturn(false);
        $active = $this->createMock(UserRole::class);
        $active->method('isActive')->willReturn(true);

        $this->repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnMap([
                [['userId' => $userId, 'role' => UserRoleService::ROLE_USER], $inactive],
                [['userId' => $userId, 'role' => UserRoleService::ROLE_ADMIN], $active],
            ]);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->hasAnyRole($userId, $roles);
        $this->assertTrue($result);
    }

    public function testHasAnyRoleWhenNoneMatch(): void
    {
        $userId = 'user-12';
        $roles = [UserRoleService::ROLE_MERCHANT, UserRoleService::ROLE_OFFICER];
        // Both not found
        $this->repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->hasAnyRole($userId, $roles);
        $this->assertFalse($result);
    }

    public function testRevokeAllRoles(): void
    {
        $userId = 'user-13';
        $roles = [$this->createMock(UserRole::class), $this->createMock(UserRole::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($roles);
        $this->em->expects($this->exactly(2))->method('remove');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('All user roles revoked', ['user_id' => $userId, 'count' => 2]);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->revokeAllRoles($userId);
        $this->assertSame(2, $result);
    }

    public function testGetUsersByRole(): void
    {
        $role = UserRoleService::ROLE_CITIZEN;
        $roles = [$this->createMock(UserRole::class)];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['role' => $role])
            ->willReturn($roles);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->getUsersByRole($role);
        $this->assertSame($roles, $result);
    }

    public function testCleanupExpiredRoles(): void
    {
        $now = new \DateTime();
        $expired1 = $this->createMock(UserRole::class);
        $expired2 = $this->createMock(UserRole::class);
        $expiredRoles = [$expired1, $expired2];
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('r.expiresAt < :now')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('now', $this->anything())->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($expiredRoles);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);
        $this->em->expects($this->exactly(2))->method('remove');
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Expired roles cleaned up', ['count' => 2]);

        $this->service = new UserRoleService($this->em, $this->logger);
        $result = $this->service->cleanupExpiredRoles();
        $this->assertSame(2, $result);
    }

    public function testGetAvailableRoles(): void
    {
        $expected = [
            UserRoleService::ROLE_ADMIN,
            UserRoleService::ROLE_OFFICER,
            UserRoleService::ROLE_CITIZEN,
            UserRoleService::ROLE_DRIVER,
            UserRoleService::ROLE_MERCHANT,
            UserRoleService::ROLE_USER,
        ];
        $this->service = new UserRoleService($this->em, $this->logger);
        $this->assertSame($expected, $this->service->getAvailableRoles());
    }

    public function testIsValidRoleAcceptsValid(): void
    {
        $this->service = new UserRoleService($this->em, $this->logger);
        $this->assertTrue($this->service->isValidRole(UserRoleService::ROLE_ADMIN));
        $this->assertTrue($this->service->isValidRole(UserRoleService::ROLE_USER));
    }

    public function testIsValidRoleRejectsInvalid(): void
    {
        $this->service = new UserRoleService($this->em, $this->logger);
        $this->assertFalse($this->service->isValidRole('invalid_role'));
    }
}
