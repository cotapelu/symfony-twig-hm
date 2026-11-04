<?php

declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Repository;

use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Infrastructure\Repository\IncidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IncidentRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $doctrineRepo;
    private EntityRepository&MockObject $qbRepo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;
    private IncidentRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineRepo = $this->createMock(EntityRepository::class);
        $this->qbRepo = $this->createMock(EntityRepository::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        foreach (['select', 'where', 'andWhere', 'setParameter', 'groupBy', 'orderBy', 'setMaxResults', 'setFirstResult'] as $method) {
            $this->qb->method($method)->willReturnSelf();
        }
        $this->qb->method('getQuery')->willReturn($this->query);

        $this->repo = new IncidentRepository($this->em);
    }

    private function expectFindRepo(): void
    {
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($this->doctrineRepo);
    }

    private function expectQbRepo(): void
    {
        $this->qbRepo->method('createQueryBuilder')->willReturn($this->qb);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($this->qbRepo);
    }

    // ================= write operations =================
    public function testSavePersistsAndFlushes(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->em->expects($this->once())->method('persist')->with($incident);
        $this->em->expects($this->once())->method('flush');

        $this->repo->save($incident);
        $this->addToAssertionCount(1);
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->em->expects($this->once())->method('remove')->with($incident);
        $this->em->expects($this->once())->method('flush');

        $this->repo->delete($incident);
        $this->addToAssertionCount(1);
    }

    // ================= lookups =================
    public function testFindDelegatesToEntityManager(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->em->expects($this->once())->method('find')->with(Incident::class, 7)->willReturn($incident);

        $this->assertSame($incident, $this->repo->find(7));
    }

    public function testFindByIdAcceptsStringId(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->em->expects($this->once())->method('find')->with(Incident::class, 'INC-1')->willReturn($incident);

        $this->assertSame($incident, $this->repo->findById('INC-1'));
    }

    public function testFindByWard(): void
    {
        $expected = [$this->createMock(Incident::class)];
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['wardCode' => 'W01'], ['createdAt' => 'DESC'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->repo->findByWard('W01'));
    }

    public function testFindByOfficer(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'OFF-1'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->assertSame([], $this->repo->findByOfficer('OFF-1'));
    }

    public function testFindUnresolvedUsesOpenStatusList(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => ['reported', 'assigned', 'in_progress']], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findUnresolved();
    }

    public function testFindOverdueReturnsEmptyArray(): void
    {
        // Entity has no resolutionDeadline field — documented behavior.
        $this->em->expects($this->never())->method('getRepository');
        $this->assertSame([], $this->repo->findOverdue());
    }

    public function testFindByStatus(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'reported'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByStatus('reported');
    }

    public function testFindByIncidentType(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['incidentType' => 'theft'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByIncidentType('theft');
    }

    public function testFindBySeverity(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['severity' => 'high'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findBySeverity('high');
    }

    public function testFindByReporterId(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['reporterId' => 'CIT-1'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByReporterId('CIT-1');
    }

    public function testFindByIncidentNumber(): void
    {
        $incident = $this->createMock(Incident::class);
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['incidentNumber' => 'INC-2026-0001'])
            ->willReturn($incident);

        $this->assertSame($incident, $this->repo->findByIncidentNumber('INC-2026-0001'));
    }

    public function testFindByIncidentNumberNotFound(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->method('findOneBy')->willReturn(null);

        $this->assertNull($this->repo->findByIncidentNumber('nope'));
    }

    public function testFindRecentPassesLimit(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 5)
            ->willReturn([]);

        $this->repo->findRecent(5);
    }

    // ================= counts =================
    public function testCount(): void
    {
        $this->expectQbRepo();
        $this->query->method('getSingleScalarResult')->willReturn('42');

        $this->assertSame(42, $this->repo->count());
    }

    public function testCountByWard(): void
    {
        $this->expectQbRepo();
        $this->qb->expects($this->once())->method('andWhere')->with('i.wardCode = :wardCode')->willReturnSelf();
        $this->query->method('getSingleScalarResult')->willReturn('3');

        $this->assertSame(3, $this->repo->countByWard('W01'));
    }

    public function testCountByStatus(): void
    {
        $rows = [
            ['status' => 'reported', 'count' => 2],
            ['status' => 'resolved', 'count' => 5],
        ];
        $this->expectQbRepo();
        $this->query->method('getResult')->willReturn($rows);

        $counts = $this->repo->countByStatus();
        $this->assertSame(['reported' => 2, 'resolved' => 5], $counts);
    }

    public function testCountByType(): void
    {
        $rows = [['incidentType' => 'theft', 'count' => 4]];
        $this->expectQbRepo();
        $this->query->method('getResult')->willReturn($rows);

        $this->assertSame(['theft' => 4], $this->repo->countByType());
    }

    public function testCountByTypeWithWard(): void
    {
        $rows = [['incidentType' => 'noise', 'count' => 1]];
        $this->expectQbRepo();
        $this->qb->expects($this->once())->method('andWhere')->with('i.wardCode = :wardCode')->willReturnSelf();
        $this->query->method('getResult')->willReturn($rows);

        $this->assertSame(['noise' => 1], $this->repo->countByTypeWithWard('W01'));
    }

    public function testCountByWardCode(): void
    {
        $rows = [['status' => 'reported', 'count' => 7]];
        $this->expectQbRepo();
        $this->qb->expects($this->once())->method('andWhere')->with('i.wardCode = :wardCode')->willReturnSelf();
        $this->query->method('getResult')->willReturn($rows);

        $this->assertSame(['reported' => 7], $this->repo->countByWardCode('W01'));
    }

    // ================= paged / query methods =================
    public function testFindOpenIncidentsAppliesPaging(): void
    {
        $expected = [$this->createMock(Incident::class)];
        $this->expectQbRepo();
        $this->qb->expects($this->once())->method('setMaxResults')->with(20)->willReturnSelf();
        $this->qb->expects($this->once())->method('setFirstResult')->with(10)->willReturnSelf();
        $this->query->method('getResult')->willReturn($expected);

        $this->assertSame($expected, $this->repo->findOpenIncidents(20, 10));
    }

    public function testSearchWithoutCriteriaAddsNoConditions(): void
    {
        $this->expectQbRepo();
        $this->qb->expects($this->never())->method('andWhere');
        $this->query->method('getResult')->willReturn([]);

        $this->assertSame([], $this->repo->search([]));
    }

    public function testSearchWithAllCriteria(): void
    {
        $this->expectQbRepo();
        // 5 !empty criteria + 2 date bounds
        $this->qb->expects($this->exactly(7))->method('andWhere');
        $this->qb->expects($this->exactly(7))->method('setParameter');
        $this->query->method('getResult')->willReturn([]);

        $this->repo->search([
            'status' => 'reported',
            'type' => 'theft',
            'wardCode' => 'W01',
            'severity' => 'high',
            'assignedOfficerId' => 'OFF-1',
            'dateFrom' => '2026-01-01',
            'dateTo' => '2026-12-31',
        ]);
    }

    public function testSearchIgnoresEmptyStringValues(): void
    {
        $this->expectQbRepo();
        // status/type/ward/severity/officer are empty -> no andWhere; dates unset
        $this->qb->expects($this->never())->method('andWhere');
        $this->query->method('getResult')->willReturn([]);

        $this->repo->search(['status' => '', 'severity' => '']);
    }
}
