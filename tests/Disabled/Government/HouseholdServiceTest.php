<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\HouseholdService;
use App\Entity\Php\Household;
use App\Repository\Php\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;
// Using stdlib exceptions as per existing codebase pattern

class HouseholdServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private HouseholdRepository $repository;
    private HouseholdService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(HouseholdRepository::class);
        $this->service = new HouseholdService($this->em, $this->repository);
    }

    public function testCreateHousehold_Success(): void
    {
        $address = '123 Le Loi Street, Ward 1';
        $wardId = 'ward-123';
        $headId = 'citizen-123';

        // Expect persist and flush (no repository find needed for new entity)
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Household $h) use ($address, $wardId, $headId) {
                return $h->getAddress() === $address
                    && $h->getWardId() === $wardId
                    && $h->getHeadId() === $headId;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createHousehold($address, $wardId, $headId);

        $this->assertInstanceOf(Household::class, $result);
        $this->assertEquals($address, $result->getAddress());
        $this->assertEquals($wardId, $result->getWardId());
        $this->assertEquals($headId, $result->getHeadId());
        $this->assertNotNull($result->getId());
    }

    public function testCreateHousehold_EmptyAddress_ThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address is required');

        $this->service->createHousehold('', null, null);
    }


    public function testGetHousehold_Exists(): void
    {
        $householdId = 'household-123';
        $household = new Household($householdId, '123 Street');
        $household->setWardId('ward-1');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $result = $this->service->getHousehold($householdId);

        $this->assertSame($household, $result);
    }

    public function testGetHousehold_NotFound_ThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Household not found');

        $this->repository->expects($this->once())
            ->method('find')
            ->with('non-existent-id')
            ->willReturn(null);

        $this->service->getHousehold('non-existent-id');
    }

    public function testUpdateHousehold_Success(): void
    {
        $householdId = 'household-123';
        $existing = new Household($householdId, 'Old Address');
        $existing->setWardId('old-ward');

        $newAddress = 'New Address';
        $newWardId = 'new-ward';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateHousehold($householdId, [
            'address' => $newAddress,
            'wardId' => $newWardId,
        ]);

        $this->assertEquals($newAddress, $result->getAddress());
        $this->assertEquals($newWardId, $result->getWardId());
    }

    public function testUpdateHousehold_InvalidAddress_ThrowsInvalidArgumentException(): void
    {
        $householdId = 'household-123';
        $existing = new Household($householdId, 'Old Address');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address is required');

        $this->service->updateHousehold($householdId, ['address' => '']);
    }

    public function testUpdateHousehold_NotFound_ThrowsRuntimeException(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with('non-existent')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Household not found');

        $this->service->updateHousehold('non-existent', ['address' => 'New']);
    }

    public function testDeleteHousehold_Success(): void
    {
        $householdId = 'household-123';
        $household = new Household($householdId, 'Address');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($household);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($household);
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteHousehold($householdId);
    }

    public function testFindByWardId_ReturnsArray(): void
    {
        $wardId = 'ward-123';
        $households = [
            new Household('hh1', 'Addr1'),
            new Household('hh2', 'Addr2'),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => $wardId])
            ->willReturn($households);

        $result = $this->service->findByWardId($wardId);

        $this->assertCount(2, $result);
    }

    public function testUpdateHousehold_OnlyWardId(): void
    {
        $householdId = 'household-123';
        $existing = new Household($householdId, 'Old Address');
        $existing->setWardId('old-ward');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($existing);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateHousehold($householdId, ['wardId' => 'new-ward']);

        $this->assertEquals('new-ward', $result->getWardId());
        $this->assertEquals('Old Address', $result->getAddress()); // unchanged
    }

    public function testUpdateHousehold_OnlyHeadId(): void
    {
        $householdId = 'household-123';
        $existing = new Household($householdId, 'Address');
        $existing->setHeadId('old-head');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($householdId)
            ->willReturn($existing);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateHousehold($householdId, ['headId' => 'new-head']);

        $this->assertEquals('new-head', $result->getHeadId());
    }

    public function testFindByWardId_ReturnsEmptyArrayWhenNone(): void
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['wardId' => 'empty-ward'])
            ->willReturn([]);

        $result = $this->service->findByWardId('empty-ward');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
