<?php

declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Repository;

use App\ANTT\Domain\Entity\SecurityCase;
use App\ANTT\Infrastructure\Repository\SecurityCaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SecurityCaseRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $doctrineRepo;
    private EntityRepository&MockObject $qbRepo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;
    private SecurityCaseRepository $repo;

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
        $this->qbRepo->method('createQueryBuilder')->willReturn($this->qb);

        $this->repo = new SecurityCaseRepository($this->em);
    }

    private function expectFindRepo(): void
    {
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(SecurityCase::class)
            ->willReturn($this->doctrineRepo);
    }

    private function expectQbRepo(): void
    {
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(SecurityCase::class)
            ->willReturn($this->qbRepo);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->em->expects($this->once())->method('persist')->with($case);
        $this->em->expects($this->once())->method('flush');

        $this->repo->save($case);
        $this->addToAssertionCount(1);
    }

    public function testFind(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->em->expects($this->once())->method('find')->with(SecurityCase::class, 9)->willReturn($case);

        $this->assertSame($case, $this->repo->find(9));
    }

    public function testFindById(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->em->expects($this->once())->method('find')->with(SecurityCase::class, 'SC-1')->willReturn($case);

        $this->assertSame($case, $this->repo->findById('SC-1'));
    }

    public function testFindByOfficer(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'OFF-1'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByOfficer('OFF-1');
    }

    public function testFindByCaseNumber(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['caseNumber' => 'SC-2026-0001'])
            ->willReturn($case);

        $this->assertSame($case, $this->repo->findByCaseNumber('SC-2026-0001'));
    }

    public function testFindByStatus(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'open'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByStatus('open');
    }

    public function testFindByPriority(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['priority' => 'high'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByPriority('high');
    }

    public function testFindByCaseType(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['type' => 'theft'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByCaseType('theft');
    }

    public function testFindByInvestigationOfficer(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'OFF-2'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByInvestigationOfficer('OFF-2');
    }

    public function testFindByWardCode(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => 'W01'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findByWardCode('W01');
    }

    public function testFindRecentPassesLimit(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 3)
            ->willReturn([]);

        $this->repo->findRecent(3);
    }

    // ================= QueryBuilder methods =================
    public function testFindOpenFiltersOpenStatus(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'open'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findOpen();
    }

    public function testFindOpenCasesFiltersOpenStatus(): void
    {
        $this->expectFindRepo();
        $this->doctrineRepo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'open'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->repo->findOpenCases();
    }

    public function testFindUnresolvedExcludesResolvedAndClosed(): void
    {
        $this->expectQbRepo();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('s.status NOT IN (:resolved, :closed)')
            ->willReturnSelf();
        // two separate named parameters (not an IN list)
        $this->qb->expects($this->exactly(2))->method('setParameter');
        $this->query->method('getResult')->willReturn([]);

        $this->repo->findUnresolved();
    }

    public function testCount(): void
    {
        $this->expectQbRepo();
        $this->query->method('getSingleScalarResult')->willReturn('17');

        $this->assertSame(17, $this->repo->count());
    }

    public function testSearchWithoutCriteria(): void
    {
        $this->expectQbRepo();
        $this->qb->expects($this->never())->method('andWhere');
        $this->query->method('getResult')->willReturn([]);

        $this->assertSame([], $this->repo->search([]));
    }

    public function testSearchWithAllCriteria(): void
    {
        $this->expectQbRepo();
        // 6 !empty criteria + 2 date bounds
        $this->qb->expects($this->exactly(8))->method('andWhere');
        $this->qb->expects($this->exactly(8))->method('setParameter');
        $this->query->method('getResult')->willReturn([]);

        $this->repo->search([
            'status' => 'open',
            'type' => 'fraud',
            'priority' => 'high',
            'assignedOfficerId' => 'OFF-1',
            'wardId' => 'WARD-1',
            'relatedIncidentId' => 'INC-9',
            'dateFrom' => '2026-01-01',
            'dateTo' => '2026-12-31',
        ]);
    }

    public function testCountByStatus(): void
    {
        $rows = [['status' => 'open', 'count' => 3]];
        $this->expectQbRepo();
        $this->query->method('getResult')->willReturn($rows);

        $this->assertSame(['open' => 3], $this->repo->countByStatus());
    }

    public function testCountByType(): void
    {
        $rows = [['type' => 'fraud', 'count' => 2]];
        $this->expectQbRepo();
        $this->query->method('getResult')->willReturn($rows);

        $this->assertSame(['fraud' => 2], $this->repo->countByType());
    }
}
