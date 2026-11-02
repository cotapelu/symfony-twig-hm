<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryVehicleService;
use App\Entity\Php\DeliveryVehicle;
use App\Repository\Php\DeliveryVehicleRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryVehicleServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryVehicleRepository $repository;
    private DeliveryVehicleService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryVehicleRepository::class);
        $this->service = new DeliveryVehicleService($this->em, $this->repository);
    }

    public function testCreateVehicle_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createVehicle(
            'MOTORCYCLE',
            'Honda SH',
            2,
            '30A-12345',
            'ACTIVE',
            ['color' => 'red']
        );

        $this->assertInstanceOf(DeliveryVehicle::class, $result);
        $this->assertEquals('MOTORCYCLE', $result->getType());
        $this->assertEquals('Honda SH', $result->getModel());
        $this->assertEquals(2, $result->getCapacity());
        $this->assertEquals('30A-12345', $result->getPlateNumber());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateVehicle_EmptyType_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createVehicle('', 'Model', 1);
    }

    public function testCreateVehicle_InvalidCapacity_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createVehicle('TYPE', 'Model', 0);
    }

    public function testGetVehicle_Exists(): void
    {
        $vehicle = new DeliveryVehicle('id-1', 'CAR', 'Toyota', 4);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($vehicle);
        $result = $this->service->getVehicle('id-1');
        $this->assertSame($vehicle, $result);
    }

    public function testGetVehicle_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getVehicle('missing');
    }

    public function testUpdateVehicle_Success(): void
    {
        $vehicle = new DeliveryVehicle('id-1', 'CAR', 'Old Model', 2);
        $this->repository->expects($this->once())->method('find')->willReturn($vehicle);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateVehicle('id-1', [
            'type' => 'MOTORCYCLE',
            'capacity' => 1,
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('MOTORCYCLE', $result->getType());
        $this->assertEquals(1, $result->getCapacity());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateVehicle_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateVehicle('missing', ['type' => 'NEW']);
    }

    public function testDeleteVehicle_Success(): void
    {
        $vehicle = new DeliveryVehicle('id-1', 'CAR', 'Model', 4);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($vehicle);
        $this->em->expects($this->once())->method('remove')->with($vehicle);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteVehicle('id-1');
    }

    public function testFindByType_ReturnsArray(): void
    {
        $vehicles = [new DeliveryVehicle('id-1', 'CAR', 'Model', 4)];
        $this->repository->expects($this->once())->method('findBy')->with(['type' => 'CAR'])->willReturn($vehicles);
        $result = $this->service->findByType('CAR');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $vehicles = [new DeliveryVehicle('id-1', 'CAR', 'Model', 4)];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'ACTIVE'])->willReturn($vehicles);
        $result = $this->service->findByStatus('ACTIVE');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyPlateNumber(): void
    {
        $vehicle = new DeliveryVehicle('id-1', 'CAR', 'Model', 4);
        $this->repository->expects($this->once())->method('find')->willReturn($vehicle);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateVehicle('id-1', ['plateNumber' => 'NEW-PLATE']);

        $this->assertEquals('NEW-PLATE', $result->getPlateNumber());
    }
}
