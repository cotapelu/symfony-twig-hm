<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use App\Service\Php\DeliveryOrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeliveryOrderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryOrderRepository&MockObject $repository;
    private DeliveryOrderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryOrderRepository::class);
        $this->service = new DeliveryOrderService($this->em, $this->repository);
    }

    public function testCreateOrder(): void
    {
        $storeId = 'store_123';
        $customerId = 'customer_456';
        $type = 'DELIVERY';
        $totalCents = 1999;
        $deliveryAddress = ['city' => 'Hanoi', 'street' => '123 Main'];
        $metadata = ['priority' => 'high'];

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryOrder $o) use (
                $storeId, $customerId, $type, $totalCents, $deliveryAddress, $metadata
            ) {
                return $o->getStoreId() === $storeId
                    && $o->getCustomerId() === $customerId
                    && $o->getType() === $type
                    && $o->getTotalCents() === $totalCents
                    && $o->getDeliveryAddress() === $deliveryAddress
                    && $o->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createOrder($storeId, $customerId, $type, $totalCents, $deliveryAddress, $metadata);
        $this->assertInstanceOf(DeliveryOrder::class, $result);
        $this->assertNotNull($result->getId());
        $this->assertSame($storeId, $result->getStoreId());
        $this->assertSame($customerId, $result->getCustomerId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($totalCents, $result->getTotalCents());
    }

    public function testCreateOrderMinimal(): void
    {
        $storeId = 'store_123';
        $customerId = 'customer_456';
        $type = 'PICKUP';
        $totalCents = 500;

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createOrder($storeId, $customerId, $type, $totalCents);
        $this->assertNull($result->getDeliveryAddress());
        $this->assertNull($result->getMetadata());
    }

    public function testGetOrder(): void
    {
        $id = 'order_123';
        $order = new DeliveryOrder($id, 'store', 'customer', 'DELIVERY', 1000);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($order);

        $result = $this->service->getOrder($id);
        $this->assertSame($order, $result);
    }

    public function testGetOrdersByStore(): void
    {
        $storeId = 'store_123';
        $orders = [
            new DeliveryOrder('id1', $storeId, 'cust1', 'DELIVERY', 1000),
            new DeliveryOrder('id2', $storeId, 'cust2', 'PICKUP', 500),
        ];

        $this->repository->expects($this->once())
            ->method('findByStoreId')
            ->with($storeId)
            ->willReturn($orders);

        $result = $this->service->getOrdersByStore($storeId);
        $this->assertCount(2, $result);
    }

    public function testGetOrdersByCustomer(): void
    {
        $customerId = 'customer_123';
        $orders = [
            new DeliveryOrder('id1', 'store1', $customerId, 'DELIVERY', 1000),
            new DeliveryOrder('id2', 'store2', $customerId, 'DELIVERY', 2000),
        ];

        $this->repository->expects($this->once())
            ->method('findByCustomerId')
            ->with($customerId)
            ->willReturn($orders);

        $result = $this->service->getOrdersByCustomer($customerId);
        $this->assertCount(2, $result);
    }

    public function testGetOrdersByDeliveryPerson(): void
    {
        $deliveryPersonId = 'delivery_123';
        $orders = [
            new DeliveryOrder('id1', 'store', 'cust', 'DELIVERY', 1000, 'CONFIRMED'),
        ];
        $orders[0]->setDeliveryPersonId($deliveryPersonId);

        $this->repository->expects($this->once())
            ->method('findByDeliveryPersonId')
            ->with($deliveryPersonId)
            ->willReturn($orders);

        $result = $this->service->getOrdersByDeliveryPerson($deliveryPersonId);
        $this->assertCount(1, $result);
    }

    public function testAssignDeliveryPersonSuccess(): void
    {
        $orderId = 'order_123';
        $deliveryPersonId = 'delivery_456';
        $order = new DeliveryOrder($orderId, 'store', 'customer', 'DELIVERY', 1000, 'PENDING');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->assignDeliveryPerson($orderId, $deliveryPersonId);
        $this->assertSame($order, $result);
        $this->assertSame($deliveryPersonId, $order->getDeliveryPersonId());
        $this->assertSame('CONFIRMED', $order->getStatus());
    }

    public function testAssignDeliveryPersonNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->assignDeliveryPerson('nonexistent', 'delivery');
        $this->assertNull($result);
    }

    public function testUpdateStatus(): void
    {
        $orderId = 'order_123';
        $order = new DeliveryOrder($orderId, 'store', 'customer', 'DELIVERY', 1000, 'PENDING');
        $newStatus = 'IN_PROGRESS';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateStatus($orderId, $newStatus);
        $this->assertSame($order, $result);
        $this->assertSame($newStatus, $order->getStatus());
    }

    public function testUpdateStatusNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $result = $this->service->updateStatus('nonexistent', 'SOME');
        $this->assertNull($result);
    }

    public function testCancelOrder(): void
    {
        $orderId = 'order_123';
        $order = new DeliveryOrder($orderId, 'store', 'customer', 'DELIVERY', 1000, 'PENDING');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cancelOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('CANCELLED', $order->getStatus());
    }

    public function testCompleteOrder(): void
    {
        $orderId = 'order_123';
        $order = new DeliveryOrder($orderId, 'store', 'customer', 'DELIVERY', 1000, 'IN_PROGRESS');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->completeOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('DELIVERED', $order->getStatus());
    }

    public function testGetActiveOrders(): void
    {
        $orders = [
            new DeliveryOrder('id1', 'store', 'customer', 'DELIVERY', 1000, 'PENDING'),
            new DeliveryOrder('id2', 'store', 'customer', 'DELIVERY', 2000, 'CONFIRMED'),
        ];

        $this->repository->expects($this->once())
            ->method('findActiveOrders')
            ->willReturn($orders);

        $result = $this->service->getActiveOrders();
        $this->assertCount(2, $result);
    }

    public function testGetOrdersByStatus(): void
    {
        $status = 'DELIVERED';
        $orders = [
            new DeliveryOrder('id1', 'store', 'customer', 'DELIVERY', 1000, $status),
        ];

        $this->repository->expects($this->once())
            ->method('findByStatus')
            ->with($status)
            ->willReturn($orders);

        $result = $this->service->getOrdersByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testCountByStore(): void
    {
        $storeId = 'store_123';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(o.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.storeId = :storeId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('storeId', $storeId)
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
            ->with('o')
            ->willReturn($qb);

        $result = $this->service->countByStore($storeId);
        $this->assertSame($count, $result);
    }

    public function testCountByCustomer(): void
    {
        $customerId = 'customer_123';
        $count = 3;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(o.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.customerId = :customerId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('customerId', $customerId)
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
            ->with('o')
            ->willReturn($qb);

        $result = $this->service->countByCustomer($customerId);
        $this->assertSame($count, $result);
    }

    public function testGetTotalRevenueByStore(): void
    {
        $storeId = 'store_123';
        $total = 49999; // $499.99

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('SUM(o.totalCents)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.storeId = :storeId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('storeId', $storeId)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $total);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $result = $this->service->getTotalRevenueByStore($storeId);
        $this->assertSame($total, $result);
    }

    public function testGetOrdersInDateRange(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime();
        $orders = [
            new DeliveryOrder('id1', 'store', 'customer', 'DELIVERY', 1000),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.createdAt BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('start', $start->format('Y-m-d H:i:s'))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('end', $end->format('Y-m-d H:i:s'))
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($orders);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $result = $this->service->getOrdersInDateRange($start, $end);
        $this->assertCount(1, $result);
    }
}
