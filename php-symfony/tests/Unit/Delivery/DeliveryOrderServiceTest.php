<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryOrderService;
use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryOrderRepository $repository;
    private DeliveryOrderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryOrderRepository::class);
        $this->service = new DeliveryOrderService($this->em, $this->repository);
    }

    public function testCreateOrder_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryOrder(
            'store-123',
            'customer-456',
            'DELIVERY',
            500000,
            ['address' => '123 St'],
            'delivery-person-1',
            'CONFIRMED',
            ['notes' => 'Fragile']
        );

        $this->assertInstanceOf(DeliveryOrder::class, $result);
        $this->assertEquals('store-123', $result->getStoreId());
        $this->assertEquals('customer-456', $result->getCustomerId());
        $this->assertEquals('DELIVERY', $result->getType());
        $this->assertEquals(500000, $result->getTotalCents());
        $this->assertEquals('CONFIRMED', $result->getStatus());
    }

    public function testCreateOrder_EmptyFields_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryOrder('', 'cust', 'TYPE', 1000);
    }

    public function testGetOrder_Exists(): void
    {
        $order = new DeliveryOrder('id-1', 'store-1', 'cust-1', 'TYPE', 1000);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($order);
        $result = $this->service->getDeliveryOrder('id-1');
        $this->assertSame($order, $result);
    }

    public function testGetOrder_NotFound_Throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getDeliveryOrder('missing');
    }

    public function testUpdateOrder_Success(): void
    {
        $order = new DeliveryOrder('id-1', 'store-1', 'cust-1', 'TYPE', 1000);
        $this->repository->expects($this->once())->method('find')->willReturn($order);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryOrder('id-1', [
            'status' => 'DELIVERED',
            'totalCents' => 2000,
            'deliveryPersonId' => 'person-2',
        ]);

        $this->assertEquals('DELIVERED', $result->getStatus());
        $this->assertEquals(2000, $result->getTotalCents());
        $this->assertEquals('person-2', $result->getDeliveryPersonId());
    }

    public function testUpdateOrder_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryOrder('missing', ['status' => 'NEW']);
    }

    public function testDeleteOrder_Success(): void
    {
        $order = new DeliveryOrder('id-1', 'store-1', 'cust-1', 'TYPE', 1000);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($order);
        $this->em->expects($this->once())->method('remove')->with($order);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryOrder('id-1');
    }

    public function testFindByStoreId_ReturnsArray(): void
    {
        $orders = [new DeliveryOrder('id-1', 'store-123', 'cust-1', 'TYPE', 1000)];
        $this->repository->expects($this->once())->method('findBy')->with(['storeId' => 'store-123'])->willReturn($orders);
        $result = $this->service->findByStoreId('store-123');
        $this->assertCount(1, $result);
    }

    public function testFindByCustomerId_ReturnsArray(): void
    {
        $orders = [new DeliveryOrder('id-1', 'store-1', 'customer-456', 'TYPE', 1000)];
        $this->repository->expects($this->once())->method('findBy')->with(['customerId' => 'customer-456'])->willReturn($orders);
        $result = $this->service->findByCustomerId('customer-456');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $orders = [new DeliveryOrder('id-1', 'store-1', 'cust-1', 'TYPE', 1000)];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'PENDING'])->willReturn($orders);
        $result = $this->service->findByStatus('PENDING');
        $this->assertCount(1, $result);
    }

    public function testFindByDeliveryPersonId_ReturnsArray(): void
    {
        $orders = [new DeliveryOrder('id-1', 'store-1', 'cust-1', 'TYPE', 1000, null, 'person-1')];
        $this->repository->expects($this->once())->method('findBy')->with(['deliveryPersonId' => 'person-1'])->willReturn($orders);
        $result = $this->service->findByDeliveryPersonId('person-1');
        $this->assertCount(1, $result);
    }
}
