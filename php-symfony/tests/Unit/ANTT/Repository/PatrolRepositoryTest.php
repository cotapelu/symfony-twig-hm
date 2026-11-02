<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Domain\Entity\Patrol;
use App\ANTT\Infrastructure\Repository\PatrolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PatrolRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $doctrineRepo;
    private PatrolRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineRepo = $this->createMock(EntityRepository::class);
        $this->repo = new PatrolRepository($this->em);
    }

    private function expectGetRepository(): void
    {
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Patrol::class)
            ->willReturn($this->doctrineRepo);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $patrol = $this->createMock(Patrol::class);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($patrol)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->save($patrol);
        $this->addToAssertionCount(1);
    }

    public function testFind(): void
    {
        $id = 123;
        $patrol = $this->createMock(Patrol::class);

        $this->em->expects($this->once())
            ->method('find')
            ->with(Patrol::class, $id)
            ->willReturn($patrol);

        $result = $this->repo->find($id);
        $this->assertSame($patrol, $result);
    }

    public function testFindById(): void
    {
        $id = 'abc-123';
        $patrol = $this->createMock(Patrol::class);

        $this->em->expects($this->once())
            ->method('find')
            ->with(Patrol::class, $id)
            ->willReturn($patrol);

        $result = $this->repo->findById($id);
        $this->assertSame($patrol, $result);
    }

    public function testFindByWard(): void
    {
        $wardId = 'W001';
        $patrol1 = $this->createMock(Patrol::class);
        $patrol2 = $this->createMock(Patrol::class);
        $expected = [$patrol1, $patrol2];

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['wardCode' => $wardId], ['patrolDate' => 'DESC'])
            ->willReturn($expected);

        $result = $this->repo->findByWard($wardId);
        $this->assertSame($expected, $result);
    }

    public function testFindByWardCode(): void
    {
        $wardCode = 'WC02';
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['wardCode' => $wardCode], ['patrolDate' => 'DESC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findByWardCode($wardCode);
        $this->assertCount(1, $result);
    }

    public function testFindByOfficer(): void
    {
        $officerId = 'OFF-123';
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['teamLeaderId' => $officerId], ['patrolDate' => 'DESC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findByOfficer($officerId);
        $this->assertCount(1, $result);
    }

    public function testFindByTeamId(): void
    {
        $teamId = 'TEAM-1';
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['teamId' => $teamId], ['patrolDate' => 'DESC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findByTeamId($teamId);
        $this->assertCount(1, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'in_progress';
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['patrolDate' => 'DESC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testFindByDate(): void
    {
        $date = new \DateTimeImmutable('2025-06-15');
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['patrolDate' => $date], ['startTime' => 'ASC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findByDate($date);
        $this->assertCount(1, $result);
    }

    public function testFindScheduled(): void
    {
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'scheduled'], ['patrolDate' => 'ASC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findScheduled();
        $this->assertCount(1, $result);
    }

    public function testFindInProgress(): void
    {
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'in_progress'], ['patrolDate' => 'DESC'])
            ->willReturn([$patrol]);

        $result = $this->repo->findInProgress();
        $this->assertCount(1, $result);
    }

    public function testFindUpcoming(): void
    {
        $patrol = $this->createMock(Patrol::class);
        $limit = 5;

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'scheduled'], ['patrolDate' => 'ASC'], $limit)
            ->willReturn([$patrol]);

        $result = $this->repo->findUpcoming($limit);
        $this->assertCount(1, $result);
    }

    public function testFindRecent(): void
    {
        $patrol = $this->createMock(Patrol::class);
        $limit = 10;

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['patrolDate' => 'DESC'], $limit)
            ->willReturn([$patrol]);

        $result = $this->repo->findRecent($limit);
        $this->assertCount(1, $result);
    }

    public function testFindByPatrolNumber(): void
    {
        $number = 'PT20250615-ABC12';
        $patrol = $this->createMock(Patrol::class);

        $this->expectGetRepository();
        $this->doctrineRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['patrolNumber' => $number])
            ->willReturn($patrol);

        $result = $this->repo->findByPatrolNumber($number);
        $this->assertSame($patrol, $result);
    }

    public function testCount(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->expectGetRepository();
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('42');

        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repo->count();
        $this->assertSame(42, $result);
    }

    public function testCountByOfficer(): void
    {
        $officerId = 'OFF-999';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->expectGetRepository();
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.teamLeaderId = :officerId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('officerId', $officerId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('7');

        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repo->countByOfficer($officerId);
        $this->assertSame(7, $result);
    }

    public function testCountByWard(): void
    {
        $wardId = 'WARD-X';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->expectGetRepository();
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.wardCode = :wardId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('wardId', $wardId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('12');

        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repo->countByWard($wardId);
        $this->assertSame(12, $result);
    }

    public function testFindByDateRange(): void
    {
        $start = new \DateTimeImmutable('2025-06-01');
        $end = new \DateTimeImmutable('2025-06-30');
        $patrol = $this->createMock(Patrol::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->expectGetRepository();

        $andWhereCalls = [];
        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function ($sql) use (&$andWhereCalls, $qb) {
                $andWhereCalls[] = $sql;
                return $qb;
            });

        $setParams = [];
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$setParams, $qb) {
                $setParams[$name] = $value;
                return $qb;
            });

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.patrolDate', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$patrol]);

        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repo->findByDateRange($start, $end);
        $this->assertCount(1, $result);
        $this->assertSame(['p.patrolDate >= :start', 'p.patrolDate <= :end'], $andWhereCalls);
        $this->assertArrayHasKey('start', $setParams);
        $this->assertArrayHasKey('end', $setParams);
        $this->assertSame($start, $setParams['start']);
        $this->assertSame($end, $setParams['end']);
    }

    public function testSearchByOfficerId(): void
    {
        $officerId = 'OFF-555';
        $patrol = $this->createMock(Patrol::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Patrol::class)
            ->willReturn($this->doctrineRepo);
        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.teamLeaderId = :officerId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('officerId', $officerId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$patrol]);

        $criteria = ['officerId' => $officerId];
        $result = $this->repo->search($criteria);
        $this->assertCount(1, $result);
    }

    public function testSearchByWardId(): void
    {
        $wardId = 'WARD-01';
        $patrol = $this->createMock(Patrol::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Patrol::class)
            ->willReturn($this->doctrineRepo);
        $this->doctrineRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.wardCode = :wardId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('wardId', $wardId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$patrol]);

        $criteria = ['wardId' => $wardId];
        $result = $this->repo->search($criteria);
        $this->assertCount(1, $result);
    }

    public function testDeletePersistsAndFlushes(): void
    {
        $patrol = $this->createMock(Patrol::class);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($patrol)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repo->delete($patrol);
        $this->addToAssertionCount(1);
    }
}
