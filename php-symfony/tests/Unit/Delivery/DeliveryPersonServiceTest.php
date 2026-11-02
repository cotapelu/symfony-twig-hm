<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryPersonService;
use App\Entity\Php\DeliveryPerson;
use App\Repository\Php\DeliveryPersonRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryPersonRepository $repository;
    private DeliveryPersonService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryPersonRepository::class);
        $this->service = new DeliveryPersonService($this->em, $this->repository);
    }

    public function testCreateDeliveryPerson_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryPerson('user-1', 'John Doe', '0901234567');

        $this->assertInstanceOf(DeliveryPerson::class, $result);
        $this->assertEquals('user-1', $result->getUserId());
        $this->assertEquals('John Doe', $result->getName());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateDeliveryPerson_EmptyFields_ThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryPerson('', 'Name', 'Phone');
    }

    public function testGetDeliveryPerson_Exists(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', '0901234567');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($person);
        $result = $this->service->getDeliveryPerson('id-1');
        $this->assertSame($person, $result);
    }

    public function testGetDeliveryPerson_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getDeliveryPerson('missing');
    }

    public function testUpdateDeliveryPerson_Success(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', '0901234567');
        $this->repository->expects($this->once())->method('find')->willReturn($person);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryPerson('id-1', [
            'name' => 'Jane',
            'phone' => '0987654321',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('Jane', $result->getName());
        $this->assertEquals('0987654321', $result->getPhone());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateDeliveryPerson_InvalidName_Throws(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', 'Phone');
        $this->repository->expects($this->once())->method('find')->willReturn($person);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateDeliveryPerson('id-1', ['name' => '']);
    }

    public function testUpdateDeliveryPerson_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryPerson('missing', ['name' => 'New']);
    }

    public function testDeleteDeliveryPerson_Success(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', 'Phone');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($person);
        $this->em->expects($this->once())->method('remove')->with($person);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryPerson('id-1');
    }

    public function testFindByUserId_ReturnsArray(): void
    {
        $people = [new DeliveryPerson('id-1', 'user-1', 'John', 'Phone')];
        $this->repository->expects($this->once())->method('findBy')->with(['userId' => 'user-1'])->willReturn($people);
        $result = $this->service->findByUserId('user-1');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $people = [new DeliveryPerson('id-1', 'user-1', 'John', 'Phone')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'ACTIVE'])->willReturn($people);
        $result = $this->service->findByStatus('ACTIVE');
        $this->assertCount(1, $result);
    }

    public function testFindByVehicleId_ReturnsArray(): void
    {
        $people = [new DeliveryPerson('id-1', 'user-1', 'John', 'Phone')];
        $this->repository->expects($this->once())->method('findBy')->with(['vehicleId' => 'vehicle-1'])->willReturn($people);
        $result = $this->service->findByVehicleId('vehicle-1');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyVehicleId(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', 'Phone');
        $this->repository->expects($this->once())->method('find')->willReturn($person);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryPerson('id-1', ['vehicleId' => 'vehicle-2']);

        $this->assertEquals('vehicle-2', $result->getVehicleId());
    }

    public function testUpdate_OnlyMetadata(): void
    {
        $person = new DeliveryPerson('id-1', 'user-1', 'John', 'Phone');
        $this->repository->expects($this->once())->method('find')->willReturn($person);
        $this->em->expects($this->once())->method('flush');

        $meta = ['rating' => 5];
        $result = $this->service->updateDeliveryPerson('id-1', ['metadata' => $meta]);

        $this->assertEquals($meta, $result->getMetadata());
    }
}
