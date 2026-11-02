<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryOrderItem;
use App\Repository\Php\DeliveryOrderItemRepository;
use App\Service\Php\DeliveryOrderItemService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderItemServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryOrderItemRepository&MockObject $repository;
    private DeliveryOrderItemService $service;

    private string $itemId;
    private DeliveryOrderItem $item;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryOrderItemRepository::class);
        $this->service = new DeliveryOrderItemService($this->em, $this->repository);

        $this->itemId = bin2hex(random_bytes(16));
        $this->item = new DeliveryOrderItem(
            $this->itemId,
            'order-1',
            'prod-1',
            2,
            500
        );
    }

    public function testCreateOrderItemSuccess(): void
    {
        $orderId = 'order-123';
        $productId = 'prod-123';
        $quantity = 3;
        $priceCents = 1500;
        $notes = 'Fragile';

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryOrderItem $i) use (&$captured, $orderId, $productId, $quantity, $priceCents, $notes) {
                $captured = $i;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createOrderItem($orderId, $productId, $quantity, $priceCents, $notes);
        $this->assertInstanceOf(DeliveryOrderItem::class, $result);
        $this->assertSame($orderId, $result->getOrderId());
        $this->assertSame($productId, $result->getProductId());
        $this->assertSame($quantity, $result->getQuantity());
        $this->assertSame($priceCents, $result->getPriceCents());
        $this->assertSame($notes, $result->getNotes());
        $this->assertSame($captured, $result);
    }

    public function testCreateOrderItemValidationEmptyOrderId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID is required');
        $this->service->createOrderItem('', 'p', 1, 100);
    }

    public function testCreateOrderItemValidationEmptyProductId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product ID is required');
        $this->service->createOrderItem('o', '', 1, 100);
    }

    public function testCreateOrderItemValidationQuantityNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');
        $this->service->createOrderItem('o', 'p', 0, 100);
    }

    public function testCreateOrderItemValidationPriceNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->createOrderItem('o', 'p', 1, 0);
    }

    public function testGetOrderItemSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);

        $result = $this->service->getOrderItem($this->itemId);
        $this->assertSame($this->item, $result);
    }

    public function testGetOrderItemNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryOrderItem not found');
        $this->service->getOrderItem($this->itemId);
    }

    public function testUpdateOrderItemSuccess(): void
    {
        $newOrderId = 'order-2';
        $newProductId = 'prod-2';
        $newQuantity = 5;
        $newPrice = 2500;
        $newNotes = 'Updated notes';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'orderId' => $newOrderId,
            'productId' => $newProductId,
            'quantity' => $newQuantity,
            'priceCents' => $newPrice,
            'notes' => $newNotes,
        ];
        $result = $this->service->updateOrderItem($this->itemId, $data);
        $this->assertSame($this->item, $result);
        $this->assertSame($newOrderId, $result->getOrderId());
        $this->assertSame($newProductId, $result->getProductId());
        $this->assertSame($newQuantity, $result->getQuantity());
        $this->assertSame($newPrice, $result->getPriceCents());
        $this->assertSame($newNotes, $result->getNotes());
    }

    public function testUpdateOrderItemValidationEmptyOrderId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID cannot be empty');
        $this->service->updateOrderItem($this->itemId, ['orderId' => '']);
    }

    public function testUpdateOrderItemValidationEmptyProductId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product ID cannot be empty');
        $this->service->updateOrderItem($this->itemId, ['productId' => '']);
    }

    public function testUpdateOrderItemValidationQuantityNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');
        $this->service->updateOrderItem($this->itemId, ['quantity' => -1]);
    }

    public function testUpdateOrderItemValidationPriceNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->updateOrderItem($this->itemId, ['priceCents' => 0]);
    }

    public function testUpdateOrderItemNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateOrderItem($this->itemId, []);
    }

    public function testDeleteOrderItemSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn($this->item);
        $this->em->expects($this->once())->method('remove')->with($this->item);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteOrderItem($this->itemId);
    }

    public function testDeleteOrderItemNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->itemId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteOrderItem($this->itemId);
    }

    public function testFindByOrderId(): void
    {
        $orderId = 'order-123';
        $results = [$this->item];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['orderId' => $orderId])
            ->willReturn($results);

        $result = $this->service->findByOrderId($orderId);
        $this->assertSame($results, $result);
    }

    public function testFindByProductId(): void
    {
        $productId = 'prod-123';
        $results = [$this->item];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['productId' => $productId])
            ->willReturn($results);

        $result = $this->service->findByProductId($productId);
        $this->assertSame($results, $result);
    }
}
