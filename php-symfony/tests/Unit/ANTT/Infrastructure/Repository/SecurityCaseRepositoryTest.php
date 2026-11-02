<?php

declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use App\ANTT\Infrastructure\Repository\SecurityCaseRepository;
use App\ANTT\Domain\Entity\SecurityCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class SecurityCaseRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $entityRepository;
    private SecurityCaseRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->repository = new SecurityCaseRepository($this->entityManager);

        // Default: getRepository returns the entityRepository mock
        $this->entityManager->method('getRepository')
            ->with(SecurityCase::class)
            ->willReturn($this->entityRepository);
    }

    // ==================== save ====================

    public function testSavePersistsAndFlushes(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->entityManager->expects($this->once())->method('persist')->with($case);
        $this->entityManager->expects($this->once())->method('flush');
        $this->repository->save($case);
    }

    // ==================== find / findById ====================

    public function testFind_ReturnsEntityWhenFound(): void
    {
        $id = 123;
        $case = $this->createMock(SecurityCase::class);
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(SecurityCase::class, $id)
            ->willReturn($case);
        $result = $this->repository->find($id);
        $this->assertSame($case, $result);
    }

    public function testFind_ReturnsNullWhenNotFound(): void
    {
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(SecurityCase::class, 999)
            ->willReturn(null);
        $result = $this->repository->find(999);
        $this->assertNull($result);
    }

    public function testFindById_ReturnsEntityWhenFound(): void
    {
        $id = 'abc';
        $case = $this->createMock(SecurityCase::class);
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(SecurityCase::class, $id)
            ->willReturn($case);
        $result = $this->repository->findById($id);
        $this->assertSame($case, $result);
    }

    // ==================== findByOfficer / findByInvestigationOfficer ====================

    public function testFindByOfficer_UsesCriteriaAndOrdering(): void
    {
        $expected = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'officer1'], ['createdAt' => 'DESC'])
            ->willReturn($expected);
        $result = $this->repository->findByOfficer('officer1');
        $this->assertSame($expected, $result);
    }

    public function testFindByInvestigationOfficer_AliasesToFindByOfficer(): void
    {
        $expected = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'officerX'], ['createdAt' => 'DESC'])
            ->willReturn($expected);
        $result = $this->repository->findByInvestigationOfficer('officerX');
        $this->assertSame($expected, $result);
    }

    // ==================== findOpen / findOpenCases ====================

    public function testFindOpen_UsesCorrectCriteria(): void
    {
        $expected = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'open'], ['createdAt' => 'DESC'])
            ->willReturn($expected);
        $result = $this->repository->findOpen();
        $this->assertSame($expected, $result);
    }

    public function testFindOpenCases_AliasesToFindOpen(): void
    {
        $expected = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'open'], ['createdAt' => 'DESC'])
            ->willReturn($expected);
        $result = $this->repository->findOpenCases();
        $this->assertSame($expected, $result);
    }

    // ==================== findUnresolved ====================

    public function testFindUnresolved_UsesQueryBuilder(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $expected = [$this->createMock(SecurityCase::class)];

        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with("s.status NOT IN (:resolved, :closed)")
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with($this->anything(), $this->anything())
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('s.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expected);

        $result = $this->repository->findUnresolved();
        $this->assertSame($expected, $result);
    }

    // ==================== count ====================

    public function testCount_ReturnsInteger(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(s.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('42');

        $result = $this->repository->count();
        $this->assertSame(42, $result);
    }

    // ==================== findByCaseNumber ====================

    public function testFindByCaseNumber_UsesFindOneBy(): void
    {
        $case = $this->createMock(SecurityCase::class);
        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['caseNumber' => 'CASE-001'])
            ->willReturn($case);
        $result = $this->repository->findByCaseNumber('CASE-001');
        $this->assertSame($case, $result);
    }

    // ==================== findByStatus / findByPriority / findByCaseType ====================

    public function testFindByStatus_OrdersByCreatedAt(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'pending'], ['createdAt' => 'DESC'])
            ->willReturn($cases);
        $result = $this->repository->findByStatus('pending');
        $this->assertSame($cases, $result);
    }

    public function testFindByPriority_OrdersByCreatedAt(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['priority' => 'high'], ['createdAt' => 'DESC'])
            ->willReturn($cases);
        $result = $this->repository->findByPriority('high');
        $this->assertSame($cases, $result);
    }

    public function testFindByCaseType_OrdersByCreatedAt(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => 'fraud'], ['createdAt' => 'DESC'])
            ->willReturn($cases);
        $result = $this->repository->findByCaseType('fraud');
        $this->assertSame($cases, $result);
    }

    // ==================== findByWardCode / findByWard ====================

    public function testFindByWardCode_OrdersByCreatedAt(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => 'W001'], ['createdAt' => 'DESC'])
            ->willReturn($cases);
        $result = $this->repository->findByWardCode('W001');
        $this->assertSame($cases, $result);
    }

    public function testFindByWard_OrdersByCreatedAt(): void
    {
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => 'W002'], ['createdAt' => 'DESC'])
            ->willReturn($cases);
        $result = $this->repository->findByWard('W002');
        $this->assertSame($cases, $result);
    }

    // ==================== findRecent ====================

    public function testFindRecent_LimitsResults(): void
    {
        $limit = 5;
        $cases = [$this->createMock(SecurityCase::class)];
        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], $limit)
            ->willReturn($cases);
        $result = $this->repository->findRecent($limit);
        $this->assertSame($cases, $result);
    }
}
