<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\GovOfficer;
use App\Service\Php\GovOfficerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GovOfficerServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private GovOfficerService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new GovOfficerService($this->em, $this->logger);
    }

    public function testCreateOfficer(): void
    {
        $userId = 'user_123';
        $department = 'Urban Planning';
        $position = 'Senior Officer';
        $metadata = ['appointment_date' => '2025-01-01'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (GovOfficer $o) use ($userId, $department, $position, $metadata) {
                return $o->getUserId() === $userId
                    && $o->getDepartment() === $department
                    && $o->getPosition() === $position
                    && $o->getMetadata() === $metadata
                    && $o->getStatus() === 'ACTIVE';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Government officer created', $this->anything());

        $result = $this->service->createOfficer($userId, $department, $position, $metadata);
        $this->assertInstanceOf(GovOfficer::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($department, $result->getDepartment());
    }

    public function testCreateOfficerDuplicate(): void
    {
        $existing = new GovOfficer('id', 'user', 'Dept', 'Pos');
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User is already a government officer');
        $this->service->createOfficer('user', 'dept', 'pos');
    }

    public function testGetOfficer(): void
    {
        $id = 'officer_123';
        $officer = new GovOfficer($id, 'user', 'Dept', 'Pos');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);

        $result = $this->service->getOfficer($id);
        $this->assertSame($officer, $result);
    }

    public function testGetOfficerByUserId(): void
    {
        $userId = 'user_123';
        $officer = new GovOfficer('id', $userId, 'Dept', 'Pos');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId])
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficerByUserId($userId);
        $this->assertSame($officer, $result);
    }

    public function testGetOfficersByDepartment(): void
    {
        $department = 'Urban Planning';
        $officers = [
            new GovOfficer('id1', 'user1', $department, 'Pos1'),
            new GovOfficer('id2', 'user2', $department, 'Pos2'),
        ];
        $officers[0]->setStatus('ACTIVE');
        $officers[1]->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['department' => $department, 'status' => 'ACTIVE'])
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficersByDepartment($department);
        $this->assertCount(2, $result);
    }

    public function testGetActiveOfficers(): void
    {
        $officers = [new GovOfficer('id', 'user', 'Dept', 'Pos')];
        $officers[0]->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveOfficers();
        $this->assertCount(1, $result);
    }

    public function testGetAllOfficers(): void
    {
        $officers = [new GovOfficer('id1', 'user1', 'Dept', 'Pos'), new GovOfficer('id2', 'user2', 'Dept2', 'Pos2')];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findAll')
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getAllOfficers();
        $this->assertCount(2, $result);
    }

    public function testIsOfficer(): void
    {
        $userId = 'user_123';
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId])
            ->willReturn(new GovOfficer('id', $userId, 'Dept', 'Pos'));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->isOfficer($userId);
        $this->assertTrue($result);
    }

    public function testIsOfficerFalse(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->isOfficer('nonexistent');
        $this->assertFalse($result);
    }

    public function testIsActiveOfficer(): void
    {
        $officer = new GovOfficer('id', 'user', 'Dept', 'Pos');
        $officer->setStatus('ACTIVE');
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->isActiveOfficer('user');
        $this->assertTrue($result);
    }

    public function testUpdateOfficer(): void
    {
        $officerId = 'officer_123';
        $officer = new GovOfficer($officerId, 'user', 'Dept', 'Pos');
        $newDept = 'New Dept';
        $newPos = 'New Pos';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateOfficer($officerId, $newDept, $newPos);
        $this->assertSame($officer, $result);
        $this->assertSame($newDept, $result->getDepartment());
        $this->assertSame($newPos, $result->getPosition());
    }

    public function testUpdateOfficerNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateOfficer('nonexistent', 'Dept');
        $this->assertNull($result);
    }

    public function testDeactivateOfficer(): void
    {
        $officerId = 'officer_123';
        $officer = new GovOfficer($officerId, 'user', 'Dept', 'Pos');
        $officer->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deactivateOfficer($officerId);
        $this->assertSame($officer, $result);
        $this->assertSame('INACTIVE', $result->getStatus());
    }

    public function testReactivateOfficer(): void
    {
        $officerId = 'officer_123';
        $officer = new GovOfficer($officerId, 'user', 'Dept', 'Pos');
        $officer->setStatus('INACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->reactivateOfficer($officerId);
        $this->assertSame($officer, $result);
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    public function testAssignArea(): void
    {
        $officerId = 'officer_123';
        $officer = new GovOfficer($officerId, 'user', 'Dept', 'Pos');
        $areaId = 'area_456';
        $metadata = ['zone' => 'north'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->assignArea($officerId, $areaId, $metadata);
        $this->assertSame($officer, $result);
        $this->assertSame($areaId, $result->getAreaId());
        $this->assertSame($metadata, $result->getMetadata());
    }

    public function testGetOfficersByArea(): void
    {
        $areaId = 'area_456';
        $officers = [
            new GovOfficer('id1', 'user1', 'Dept', 'Pos', 'ACTIVE', $areaId),
            new GovOfficer('id2', 'user2', 'Dept', 'Pos', 'ACTIVE', $areaId),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['areaId' => $areaId, 'status' => 'ACTIVE'])
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficersByArea($areaId);
        $this->assertCount(2, $result);
    }

    public function testGetOfficersWithArea(): void
    {
        $officers = [
            new GovOfficer('id1', 'user1', 'Dept', 'Pos', 'ACTIVE', 'area1'),
            new GovOfficer('id2', 'user2', 'Dept', 'Pos', 'ACTIVE', 'area2'),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.areaId IS NOT NULL')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($officers);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);

        $result = $this->service->getOfficersWithArea();
        $this->assertCount(2, $result);
    }

    public function testGetOfficersWithoutArea(): void
    {
        $officers = [
            new GovOfficer('id1', 'user1', 'Dept', 'Pos', 'ACTIVE', null),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.areaId IS NULL')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($officers);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);

        $result = $this->service->getOfficersWithoutArea();
        $this->assertCount(1, $result);
    }

    public function testGetOfficersByPosition(): void
    {
        $position = 'Senior Officer';
        $officers = [new GovOfficer('id', 'user', 'Dept', $position)];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['position' => $position])
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficersByPosition($position);
        $this->assertCount(1, $result);
    }

    public function testGetOfficerCountByDepartment(): void
    {
        $department = 'Urban Planning';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(o.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.department = :department')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);

        $result = $this->service->getOfficerCountByDepartment($department);
        $this->assertSame($count, $result);
    }

    public function testGetOfficersByStatus(): void
    {
        $status = 'ACTIVE';
        $officers = [new GovOfficer('id', 'user', 'Dept', 'Pos', $status)];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($officers);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficersByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testIncrementCaseCount(): void
    {
        $officerId = 'officer_123';
        $officer = new GovOfficer($officerId, 'user', 'Dept', 'Pos');
        $officer->setCaseCount(5);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($officerId)
            ->willReturn($officer);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->incrementCaseCount($officerId);
        $this->assertSame($officer, $result);
        $this->assertSame(6, $result->getCaseCount());
    }

    public function testGetTopPerformers(): void
    {
        $limit = 3;
        $officers = [
            new GovOfficer('id1', 'user1', 'Dept', 'Pos'),
            new GovOfficer('id2', 'user2', 'Dept', 'Pos'),
        ];
        $officers[0]->setCaseCount(100);
        $officers[1]->setCaseCount(50);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('o.caseCount', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($officers);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(GovOfficer::class)
            ->willReturn($repo);

        $result = $this->service->getTopPerformers($limit);
        $this->assertCount(2, $result);
    }

    public function testGetOfficerByUserIdNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOfficerByUserId('nonexistent');
        $this->assertNull($result);
    }

    public function testDeactivateOfficerNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deactivateOfficer('nonexistent');
        $this->assertNull($result);
    }
}
