<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\Citizen;
use App\Repository\Php\CitizenRepository;
use App\Service\Php\CitizenService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CitizenServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CitizenRepository&MockObject $repository;
    private CitizenService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CitizenRepository::class);
        $this->service = new CitizenService($this->em, $this->repository);
    }

    public function testCreateCitizen(): void
    {
        $name = 'John Doe';
        $identityNumber = 'ID123456';
        $metadata = ['blood_type' => 'O+', 'emergency_contact' => 'Jane'];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Citizen $c) use ($name, $identityNumber, $metadata) {
                return $c->getName() === $name
                    && $c->getIdentityNumber() === $identityNumber
                    && $c->getMetadata() === $metadata
                    && $c->getStatus() === 'ACTIVE';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createCitizen($name, $identityNumber, $metadata);
        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertSame($name, $result->getName());
        $this->assertSame($identityNumber, $result->getIdentityNumber());
        $this->assertNotNull($result->getId());
    }

    public function testCreateCitizenMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createCitizen('Name', 'ID123');
        $this->assertNull($result->getMetadata());
        $this->assertNull($result->getHouseholdId());
    }

    public function testGetCitizen(): void
    {
        $id = 'citizen_123';
        $citizen = new Citizen($id, 'Name', 'ID123');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($citizen);

        $result = $this->service->getCitizen($id);
        $this->assertSame($citizen, $result);
    }

    public function testGetCitizenByIdentityNumber(): void
    {
        $identityNumber = 'ID123';
        $citizen = new Citizen('id', 'Name', $identityNumber);

        $this->repository->expects($this->once())
            ->method('findByIdentityNumber')
            ->with($identityNumber)
            ->willReturn($citizen);

        $result = $this->service->getCitizenByIdentityNumber($identityNumber);
        $this->assertSame($citizen, $result);
    }

    public function testGetCitizensByHousehold(): void
    {
        $householdId = 'household_123';
        $citizens = [
            new Citizen('id1', 'Name1', 'ID1', $householdId),
            new Citizen('id2', 'Name2', 'ID2', $householdId),
        ];

        $this->repository->expects($this->once())
            ->method('findByHouseholdId')
            ->with($householdId)
            ->willReturn($citizens);

        $result = $this->service->getCitizensByHousehold($householdId);
        $this->assertCount(2, $result);
    }

    public function testGetAllActiveCitizens(): void
    {
        $citizens = [new Citizen('id', 'Name', 'ID')];
        $this->repository->expects($this->once())
            ->method('findActiveCitizens')
            ->willReturn($citizens);

        $result = $this->service->getAllActiveCitizens();
        $this->assertCount(1, $result);
    }

    public function testSearchCitizens(): void
    {
        $query = 'john';
        $limit = 10;
        $citizens = [new Citizen('id', 'John Doe', 'ID')];

        $this->repository->expects($this->once())
            ->method('searchByName')
            ->with($query, $limit)
            ->willReturn($citizens);

        $result = $this->service->searchCitizens($query, $limit);
        $this->assertCount(1, $result);
    }

    public function testUpdateCitizen(): void
    {
        $id = 'citizen_123';
        $citizen = new Citizen($id, 'Old Name', 'ID123');
        $data = ['name' => 'New Name', 'status' => 'SUSPENDED'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($citizen);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateCitizen($id, $data);
        $this->assertSame($citizen, $result);
        $this->assertSame('New Name', $result->getName());
        $this->assertSame('SUSPENDED', $result->getStatus());
    }

    public function testUpdateCitizenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->updateCitizen('nonexistent', ['name' => 'Name']);
        $this->assertNull($result);
    }

    public function testDeleteCitizen(): void
    {
        $id = 'citizen_123';
        $citizen = new Citizen($id, 'Name', 'ID123');
        $citizen->setStatus('ACTIVE');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($citizen);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteCitizen($id);
        $this->assertTrue($result);
        $this->assertSame('DELETED', $citizen->getStatus());
    }

    public function testDeleteCitizenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->deleteCitizen('nonexistent');
        $this->assertFalse($result);
    }

    public function testSuspendCitizen(): void
    {
        $id = 'citizen_123';
        $citizen = new Citizen($id, 'Name', 'ID123');
        $citizen->setStatus('ACTIVE');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($citizen);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->suspendCitizen($id);
        $this->assertSame($citizen, $result);
        $this->assertSame('SUSPENDED', $result->getStatus());
    }

    public function testSuspendCitizenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->suspendCitizen('nonexistent');
        $this->assertNull($result);
    }

    public function testReactivateCitizen(): void
    {
        $id = 'citizen_123';
        $citizen = new Citizen($id, 'Name', 'ID123');
        $citizen->setStatus('SUSPENDED');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($citizen);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->reactivateCitizen($id);
        $this->assertSame($citizen, $result);
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    public function testGetCitizensByStatus(): void
    {
        $status = 'SUSPENDED';
        $citizens = [new Citizen('id', 'Name', 'ID', null, $status)];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($citizens);

        $result = $this->service->getCitizensByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testGetCitizensByMetadata(): void
    {
        $key = 'blood_type';
        $value = 'O+';
        $citizens = [new Citizen('id', 'Name', 'ID')];
        // Use reflection to set metadata for testing
        $ref = new \ReflectionProperty(Citizen::class, 'metadata');
        $ref->setAccessible(true);
        $ref->setValue($citizens[0], [$key => $value]);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with("JSON_CONTAINS(c.metadata, :value, '$')")
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('value', sprintf('"%s"', $value))
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($citizens);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $result = $this->service->getCitizensByMetadata($key, $value);
        $this->assertCount(1, $result);
    }

    public function testCountByHousehold(): void
    {
        $householdId = 'household_123';
        $count = 4;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(c.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('c.householdId = :householdId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('householdId', $householdId)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $result = $this->service->countByHousehold($householdId);
        $this->assertSame($count, $result);
    }

    public function testGetNewCitizens(): void
    {
        $days = 30;
        $citizens = [new Citizen('id', 'Name', 'ID')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('c.createdAt >= :since')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('since', $this->anything())
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($citizens);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $result = $this->service->getNewCitizens($days);
        $this->assertCount(1, $result);
    }

    public function testMergeCitizens(): void
    {
        $targetId = 'target_123';
        $sourceId = 'source_456';
        $target = new Citizen($targetId, 'Target', 'ID123');
        $source = new Citizen($sourceId, 'Source', 'ID456');

        $this->repository->expects($this->at(0))
            ->method('find')
            ->with($targetId)
            ->willReturn($target);
        $this->repository->expects($this->at(1))
            ->method('find')
            ->with($sourceId)
            ->willReturn($source);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($source);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->mergeCitizens($targetId, [$sourceId]);
        $this->assertSame($target, $result);
        // After merge, source is removed and target may have combined metadata, etc.
    }

    public function testGetCitizensWithMissingMetadata(): void
    {
        $key = 'emergency_contact';
        $citizens = [new Citizen('id', 'Name', 'ID')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with("JSON_EXTRACT(c.metadata, '$." . $key . "') IS NULL")
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($citizens);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $result = $this->service->getCitizensWithMissingMetadata($key);
        $this->assertCount(1, $result);
    }

    public function testGetCitizensByStatusEmpty(): void
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn([]);

        $result = $this->service->getCitizensByStatus('INACTIVE');
        $this->assertEmpty($result);
    }
}
