<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryOrderItemService;
use App\Entity\Php\DeliveryOrderItem;
use App\Repository\Php\DeliveryOrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderItemServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryOrderItemRepository $repository;
    private DeliveryOrderItemService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryOrderItemRepository::class);
        $this->service = new DeliveryOrderItemService($this->em, $this->repository);
    }

    public function testCreateOrderItem_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createOrderItem(
            'order-123',
            'product-456',
            2,
            500000,
            'Extra cheese'
        );

        $this->assertInstanceOf(DeliveryOrderItem::class, $result);
        $this->assertEquals('order-123', $result->getOrderId());
        $this->assertEquals('product-456', $result->getProductId());
        $this->assertEquals(2, $result->getQuantity());
        $this->assertEquals(500000, $result->getPriceCents());
    }

    public function testCreateOrderItem_InvalidFields_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createOrderItem('', 'product', 1, 1000);
    }

    public function testGetOrderItem_Exists(): void
    {
        $item = new DeliveryOrderItem('id-1', 'order-1', 'product-1', 1, 1000);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($item);
        $result = $this->service->getOrderItem('id-1');
        $this->assertSame($item, $result);
    }

    public function testGetOrderItem_NotFound_Throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getOrderItem('missing');
    }

    public function testUpdateOrderItem_Success(): void
    {
        $item = new DeliveryOrderItem('id-1', 'order-1', 'product-1', 1, 1000);
        $this->repository->expects($this->once())->method('find')->willReturn($item);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateOrderItem('id-1', [
            'quantity' => 3,
            'priceCents' => 1500,
            'notes' => 'Updated notes',
        ]);

        $this->assertEquals(3, $result->getQuantity());
        $this->assertEquals(1500, $result->getPriceCents());
        $this->assertEquals('Updated notes', $result->getNotes());
    }

    public function testUpdateOrderItem_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateOrderItem('missing', ['quantity' => 5]);
    }

    public function testDeleteOrderItem_Success(): void
    {
        $item = new DeliveryOrderItem('id-1', 'order-1', 'product-1', 1, 1000);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($item);
        $this->em->expects($this->once())->method('remove')->with($item);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteOrderItem('id-1');
    }

    public function testFindByOrderId_ReturnsArray(): void
    {
        $items = [new DeliveryOrderItem('id-1', 'order-123', 'product-1', 1, 1000)];
        $this->repository->expects($this->once())->method('findBy')->with(['orderId' => 'order-123'])->willReturn($items);
        $result = $this->service->findByOrderId('order-123');
        $this->assertCount(1, $result);
    }

    public function testFindByProductId_ReturnsArray(): void
    {
        $items = [new DeliveryOrderItem('id-1', 'order-1', 'product-456', 1, 1000)];
        $this->repository->expects($this->once())->method('findBy')->with(['productId' => 'product-456'])->willReturn($items);
        $result = $this->service->findByProductId('product-456');
        $this->assertCount(1, $result);
    }
}
