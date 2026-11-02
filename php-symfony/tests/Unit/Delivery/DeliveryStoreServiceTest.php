<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryStoreService;
use App\Entity\Php\DeliveryStore;
use App\Repository\Php\DeliveryStoreRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryStoreServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryStoreRepository $repository;
    private DeliveryStoreService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryStoreRepository::class);
        $this->service = new DeliveryStoreService($this->em, $this->repository);
    }

    public function testCreateDeliveryStore_Success(): void
    {
        $ownerId = 'owner-123';
        $type = 'RETAIL';
        $name = 'Store 1';
        $license = 'LIC-001';

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryStore $s) use ($ownerId, $type, $name, $license) {
                return $s->getOwnerId() === $ownerId
                    && $s->getType() === $type
                    && $s->getName() === $name
                    && $s->getLicense() === $license;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createDeliveryStore($ownerId, $type, $name, $license);

        $this->assertInstanceOf(DeliveryStore::class, $result);
        $this->assertEquals($ownerId, $result->getOwnerId());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateDeliveryStore_EmptyOwner_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryStore('', 'TYPE', 'Name');
    }

    public function testCreateDeliveryStore_EmptyType_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryStore('owner', '', 'Name');
    }

    public function testCreateDeliveryStore_EmptyName_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryStore('owner', 'TYPE', '');
    }

    public function testGetDeliveryStore_Exists(): void
    {
        $store = new DeliveryStore('store-1', 'owner-1', 'RETAIL', 'My Store');
        $this->repository->expects($this->once())
            ->method('find')
            ->with('store-1')
            ->willReturn($store);

        $result = $this->service->getDeliveryStore('store-1');
        $this->assertSame($store, $result);
    }

    public function testGetDeliveryStore_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with('missing')
            ->willReturn(null);
        $this->service->getDeliveryStore('missing');
    }

    public function testUpdateDeliveryStore_Success(): void
    {
        $store = new DeliveryStore('id', 'owner', 'TYPE', 'Name');
        $this->repository->expects($this->once())->method('find')->willReturn($store);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryStore('id', [
            'name' => 'New Name',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateDeliveryStore_InvalidOwner_ThrowsException(): void
    {
        $store = new DeliveryStore('id', 'owner', 'TYPE', 'Name');
        $this->repository->expects($this->once())->method('find')->willReturn($store);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateDeliveryStore('id', ['ownerId' => '']);
    }

    public function testUpdateDeliveryStore_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryStore('missing', ['name' => 'New']);
    }

    public function testDeleteDeliveryStore_Success(): void
    {
        $store = new DeliveryStore('id', 'owner', 'TYPE', 'Name');
        $this->repository->expects($this->once())->method('find')->with('id')->willReturn($store);
        $this->em->expects($this->once())->method('remove')->with($store);
        $this->em->expects($this->once())->method('flush');
        $this->service->deleteDeliveryStore('id');
    }

    public function testFindByOwnerId_ReturnsArray(): void
    {
        $stores = [new DeliveryStore('id1', 'owner-123', 'TYPE', 'Name')];
        $this->repository->expects($this->once())->method('findBy')->with(['ownerId' => 'owner-123'])->willReturn($stores);
        $result = $this->service->findByOwnerId('owner-123');
        $this->assertCount(1, $result);
    }

    public function testFindByType_ReturnsArray(): void
    {
        $stores = [new DeliveryStore('id1', 'owner', 'RETAIL', 'Name')];
        $this->repository->expects($this->once())->method('findBy')->with(['type' => 'RETAIL'])->willReturn($stores);
        $result = $this->service->findByType('RETAIL');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyLicense(): void
    {
        $store = new DeliveryStore('id', 'owner', 'TYPE', 'Name');
        $this->repository->expects($this->once())->method('find')->willReturn($store);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryStore('id', ['license' => 'NEW-LIC']);

        $this->assertEquals('NEW-LIC', $result->getLicense());
    }
}
