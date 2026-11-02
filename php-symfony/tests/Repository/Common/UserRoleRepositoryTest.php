<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\UserRoleRepository;
use App\Entity\Common\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleRepositoryTest extends TestCase
{
    private UserRoleRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(UserRoleRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'createQueryBuilder', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $role = $this->createMock(UserRole::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($role);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($role);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $role = $this->createMock(UserRole::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($role);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($role);
    }

    public function testFindById(): void
    {
        $id = 'role-123';
        $expected = $this->createMock(UserRole::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(UserRole::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByRole(): void
    {
        $role = 'ADMIN';
        $expected = [$this->createMock(UserRole::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['role' => $role])
            ->willReturn($expected);

        $result = $this->repo->findByRole($role);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserIdAndRole(): void
    {
        $userId = 'user-123';
        $role = 'EDITOR';
        $expected = $this->createMock(UserRole::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'role' => $role])
            ->willReturn($expected);

        $result = $this->repo->findByUserIdAndRole($userId, $role);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveRoles(): void
    {
        $expected = [$this->createMock(UserRole::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveRoles();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveRoles(): void
    {
        $expected = [$this->createMock(UserRole::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveRoles();
        $this->assertSame($expected, $result);
    }

    public function testDeleteByUserId(): void
    {
        $userId = 'user-123';
        $affected = 3;

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.userId = :userId')
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $result = $this->repo->deleteByUserId($userId);
        $this->assertSame($affected, $result);
    }

    public function testDeleteByUserIdAndRole(): void
    {
        $userId = 'user-123';
        $role = 'ADMIN';
        $affected = 2;

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.userId = :userId')
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.role = :role')
            ->willReturn($this->qb);
        $this->qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnMap([
                ['userId', $userId, $this->qb],
                ['role', $role, $this->qb],
            ]);
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);

        $result = $this->repo->deleteByUserIdAndRole($userId, $role);
        $this->assertSame($affected, $result);
    }
}
