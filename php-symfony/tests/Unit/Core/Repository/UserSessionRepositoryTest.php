<?php declare(strict_types=1);

namespace App\Tests\Unit\Core\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Core\Repository\UserSessionRepository;
use App\Core\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserSessionRepositoryTest extends TestCase
{
    private UserSessionRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(UserSessionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testFindByUserIdBuildsCorrectQuery(): void
    {
        $userId = 123;
        $capturedParams = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->with('s.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$capturedParams) {
                $capturedParams[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('s.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $result = $this->repo->findByUserId($userId);
        $this->assertIsArray($result);
        $this->assertSame($userId, $capturedParams['userId']);
    }

    public function testFindByTokenReturnsSingleResult(): void
    {
        $token = 'abc123';
        $session = $this->createMock(UserSession::class);

        $this->qb->expects($this->once())
            ->method('where')
            ->with('s.token = :token')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('token', $token)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($session);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $result = $this->repo->findByToken($token);
        $this->assertSame($session, $result);
    }

    public function testFindActiveSessionAppliesAllFiltersAndLimit(): void
    {
        $userId = 5;
        $session = $this->createMock(UserSession::class);
        $whereClauses = [];
        $andWhereClauses = [];
        $params = [];

        $this->qb->expects($this->once())
            ->method('where')
            ->willReturnCallback(function ($predicate) use (&$whereClauses) {
                $whereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('andWhere')
            ->willReturnCallback(function ($predicate) use (&$andWhereClauses) {
                $andWhereClauses[] = $predicate;
                return $this->qb;
            });
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('s.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($session);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $result = $this->repo->findActiveSession($userId);
        $this->assertSame($session, $result);
        $this->assertCount(1, $whereClauses);
        $this->assertStringContainsString('s.userId = :userId', $whereClauses[0]);
        $this->assertCount(2, $andWhereClauses);
        $this->assertStringContainsString('s.expiresAt > :now', $andWhereClauses[0]);
        $this->assertStringContainsString('s.isActive = true', $andWhereClauses[1]);
        $this->assertSame($userId, $params['userId']);
        $this->assertInstanceOf(\DateTime::class, $params['now']);
    }

    public function testDeactivateSessionBuildsUpdateQuery(): void
    {
        $token = 'tok123';

        $this->qb->expects($this->once())->method('update')->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('set')
            ->with('s.isActive', 'false')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('s.token = :token')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('token', $token)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->repo->deactivateSession($token);
        $this->addToAssertionCount(1);
    }

    public function testDeactivateAllSessionsBuildsUpdateQuery(): void
    {
        $userId = 42;

        $this->qb->expects($this->once())->method('update')->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('set')
            ->with('s.isActive', 'false')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('s.userId = :userId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->repo->deactivateAllSessions($userId);
        $this->addToAssertionCount(1);
    }

    public function testDeleteExpiredSessionsBuildsDeleteQuery(): void
    {
        $this->qb->expects($this->once())->method('delete')->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('s.expiresAt < :now')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('now', $this->anything())
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn(3);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $result = $this->repo->deleteExpiredSessions();
        $this->assertSame(3, $result);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $session = $this->createMock(UserSession::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($session)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->expects($this->exactly(2))
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->repo->save($session);
        $this->addToAssertionCount(1);
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $session = $this->createMock(UserSession::class);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($session)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->expects($this->exactly(2))
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->repo->delete($session);
        $this->addToAssertionCount(1);
    }
}