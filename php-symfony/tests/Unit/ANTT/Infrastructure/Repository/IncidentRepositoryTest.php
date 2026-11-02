<?php declare(strict_types=1);

namespace Tests\Unit\ANTT\Infrastructure\Repository;

use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Infrastructure\Repository\IncidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IncidentRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private IncidentRepository $repository;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = new IncidentRepository($this->em);
    }

    private function createIncidentMock(): MockObject
    {
        return $this->createMock(Incident::class);
    }

    private function createQueryBuilderMock(): MockObject
    {
        return $this->createMock(QueryBuilder::class);
    }

    private function createQueryMock(): MockObject
    {
        return $this->createMock(Query::class);
    }

    // ==================== find / findById ====================

    public function testFind_ReturnsNull_WhenNotFound(): void
    {
        $this->em->expects($this->once())
            ->method('find')
            ->with(Incident::class, 999)
            ->willReturn(null);

        $result = $this->repository->find(999);
        $this->assertNull($result);
    }

    public function testFind_ReturnsIncident_WhenFound(): void
    {
        $incident = $this->createIncidentMock();
        $this->em->expects($this->once())
            ->method('find')
            ->with(Incident::class, 123)
            ->willReturn($incident);

        $result = $this->repository->find(123);
        $this->assertSame($incident, $result);
    }

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->em->expects($this->once())
            ->method('find')
            ->with(Incident::class, 'nonexistent')
            ->willReturn(null);

        $result = $this->repository->findById('nonexistent');
        $this->assertNull($result);
    }

    public function testFindById_ReturnsIncident_WhenFound(): void
    {
        $incident = $this->createIncidentMock();
        $this->em->expects($this->once())
            ->method('find')
            ->with(Incident::class, 'inc-456')
            ->willReturn($incident);

        $result = $this->repository->findById('inc-456');
        $this->assertSame($incident, $result);
    }

    // ==================== save ====================

    public function testSave_PersistsIncident(): void
    {
        $incident = $this->createIncidentMock();

        $this->em->expects($this->once())
            ->method('persist')
            ->with($incident)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->save($incident);
        $this->addToAssertionCount(1);
    }

    // ==================== findByIncidentNumber ====================

    public function testFindByIncidentNumber_ReturnsNull_WhenNotFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findOneBy')
            ->with(['incidentNumber' => 'NUM999'])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByIncidentNumber('NUM999');
        $this->assertNull($result);
    }

    public function testFindByIncidentNumber_ReturnsIncident_WhenFound(): void
    {
        $incident = $this->createIncidentMock();
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findOneBy')
            ->with(['incidentNumber' => 'NUM123'])
            ->willReturn($incident);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByIncidentNumber('NUM123');
        $this->assertSame($incident, $result);
    }

    // ==================== findByStatus ====================

    public function testFindByStatus_ReturnsEmptyArray_WhenNone(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'pending'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByStatus('pending');
        $this->assertEmpty($result);
    }

    public function testFindByStatus_ReturnsIncidents_WhenFound(): void
    {
        $incident1 = $this->createIncidentMock();
        $incident2 = $this->createIncidentMock();
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'resolved'], ['createdAt' => 'DESC'])
            ->willReturn([$incident1, $incident2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByStatus('resolved');
        $this->assertCount(2, $result);
    }

    // ==================== findByWardCode ====================

    public function testFindByWardCode_ReturnsEmptyArray(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findBy')
            ->with(['wardCode' => 'WARD01'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByWardCode('WARD01');
        $this->assertEmpty($result);
    }

    // ==================== findByAssignedOfficer ====================

    public function testFindByAssignedOfficer_ReturnsEmptyArray(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())
            ->method('findBy')
            ->with(['assignedOfficerId' => 'OFF123'], ['createdAt' => 'DESC'])
            ->willReturn([]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->findByAssignedOfficer('OFF123');
        $this->assertEmpty($result);
    }

    // ==================== count ====================

    public function testCount_ReturnsInt(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $repoMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('i')
            ->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(i.id)')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('42');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->count();
        $this->assertSame(42, $result);
    }

    // ==================== countByWard ====================

    public function testCountByWard_ReturnsInt(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $repoMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('i')
            ->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('COUNT(i.id)')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->with('i.wardCode = :wardCode')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('wardCode', 'WARD05')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn('7');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Incident::class)
            ->willReturn($repoMock);

        $result = $this->repository->countByWard('WARD05');
        $this->assertSame(7, $result);
    }
}
