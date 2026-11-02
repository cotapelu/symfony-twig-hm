<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\Household;
use App\Service\Php\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HouseholdServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private HouseholdService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new HouseholdService($this->em, $this->logger);
    }

    public function testCreateHouseholdSuccess(): void
    {
        $address = '123 Main St';
        $wardId = 'ward_001';
        $headId = 'head_123';
        $metadata = ['notes' => 'test'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Household::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Household $h) use ($address, $wardId, $headId, $metadata) {
                return $h->getAddress() === $address
                    && $h->getWardId() === $wardId
                    && $h->getHeadId() === $headId
                    && $h->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Household created', $this->anything());

        $result = $this->service->createHousehold($address, $wardId, $headId, $metadata);
        $this->assertInstanceOf(Household::class, $result);
        $this->assertSame($address, $result->getAddress());
        $this->assertSame($wardId, $result->getWardId());
        $this->assertSame($headId, $result->getHeadId());
    }

    public function testGetHouseholdSuccess(): void
    {
        $id = 'house_123';
        $household = new Household($id, 'Address');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Household::class)
            ->willReturn($repo);

        $result = $this->service->getHousehold($id);
        $this->assertSame($household, $result);
    }

    public function testGetHouseholdNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getHousehold('nonexistent');
        $this->assertNull($result);
    }

    public function testGetHouseholdsByWard(): void
    {
        $wardId = 'ward_001';
        $h1 = new Household('id1', 'Addr1');
        $h2 = new Household('id2', 'Addr2');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => $wardId])
            ->willReturn([$h1, $h2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getHouseholdsByWard($wardId);
        $this->assertCount(2, $result);
    }

    public function testGetHouseholdByHead(): void
    {
        $headId = 'head_123';
        $household = new Household('id', 'Addr');
        $household->setHeadId($headId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['headId' => $headId])
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getHouseholdByHead($headId);
        $this->assertSame($household, $result);
    }

    public function testUpdateHouseholdSuccess(): void
    {
        $householdId = 'house_123';
        $household = new Household($householdId, 'Old Address');
        $newAddress = 'New Address';
        $newWard = 'ward_002';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Household updated', ['household_id' => $householdId]);

        $result = $this->service->updateHousehold($householdId, $newAddress, $newWard);
        $this->assertSame($household, $result);
        $this->assertSame($newAddress, $result->getAddress());
        $this->assertSame($newWard, $result->getWardId());
    }

    public function testUpdateHouseholdNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateHousehold('nonexistent', 'addr');
        $this->assertNull($result);
    }

    public function testSetHead(): void
    {
        $householdId = 'house_123';
        $headId = 'head_456';
        $household = new Household($householdId, 'Addr');
        $household->setHeadId(null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Household head set', $this->anything());

        $result = $this->service->setHead($householdId, $headId);
        $this->assertSame($household, $result);
        $this->assertSame($headId, $result->getHeadId());
    }

    public function testRemoveHead(): void
    {
        $householdId = 'house_123';
        $headId = 'head_456';
        $household = new Household($householdId, 'Addr');
        $household->setHeadId($headId);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->removeHead($householdId);
        $this->assertSame($household, $result);
        $this->assertNull($result->getHeadId());
    }

    public function testDeleteHouseholdSuccess(): void
    {
        $householdId = 'house_123';
        $household = new Household($householdId, 'Addr');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($household);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteHousehold($householdId);
        $this->assertTrue($result);
    }

    public function testDeleteHouseholdNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteHousehold('nonexistent');
        $this->assertFalse($result);
    }

    public function testCountByWard(): void
    {
        $wardId = 'ward_001';
        $count = 5;

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('count')
            ->with(['wardId' => $wardId])
            ->willReturn($count);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Household::class)
            ->willReturn($repo);

        $result = $this->service->countByWard($wardId);
        $this->assertSame($count, $result);
    }

    public function testSearchByAddress(): void
    {
        $query = 'Main';
        $h1 = new Household('id1', '123 Main St');
        $h2 = new Household('id2', '456 Main Ave');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('h.address LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('query', '%' . $query . '%')
            ->willReturnSelf();
        $query_obj = $this->createMock(\Doctrine\ORM\Query::class);
        $query_obj->expects($this->once())
            ->method('getResult')
            ->willReturn([$h1, $h2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query_obj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('h')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Household::class)
            ->willReturn($repo);

        $result = $this->service->searchByAddress($query);
        $this->assertCount(2, $result);
    }
}
