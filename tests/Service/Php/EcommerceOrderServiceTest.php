<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\EcommerceOrder;
use App\Service\Php\EcommerceOrderService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EcommerceOrderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private EcommerceOrderService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new EcommerceOrderService($this->em, $this->logger);
    }

    public function testCreateOrder(): void
    {
        $buyerId = 'buyer_123';
        $items = [
            ['productId' => 'p1', 'name' => 'Product 1', 'price' => 10.00, 'quantity' => 2],
            ['productId' => 'p2', 'name' => 'Product 2', 'price' => 20.00, 'quantity' => 1],
        ];
        $expectedTotal = 40.00;
        $shippingAddress = '123 Main St, Hanoi';
        $paymentMethod = 'CREDIT_CARD';
        $shippingPhone = '0123456789';
        $notes = 'Please deliver after 6pm';
        $metadata = ['gift' => true];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceOrder $o) use (
                $buyerId, $items, $expectedTotal, $shippingAddress, $paymentMethod, $shippingPhone, $notes, $metadata
            ) {
                return $o->getBuyerId() === $buyerId
                    && $o->getItems() === $items
                    && $o->getTotalAmount() === $expectedTotal
                    && $o->getShippingAddress() === $shippingAddress
                    && $o->getPaymentMethod() === $paymentMethod
                    && $o->getShippingPhone() === $shippingPhone
                    && $o->getNotes() === $notes
                    && $o->getMetadata() === $metadata
                    && $o->getStatus() === 'PENDING';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Ecommerce order created', $this->anything());

        $result = $this->service->createOrder($buyerId, $items, $shippingAddress, $paymentMethod, $shippingPhone, $notes, $metadata);
        $this->assertInstanceOf(EcommerceOrder::class, $result);
        $this->assertSame($buyerId, $result->getBuyerId());
        $this->assertSame($expectedTotal, $result->getTotalAmount());
        $this->assertSame('PENDING', $result->getStatus());
    }

    public function testCreateOrderMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createOrder('buyer', [['p' => 'product', 'price' => 10, 'quantity' => 1]], 'Addr', 'CASH');
        $this->assertNull($result->getShippingPhone());
        $this->assertNull($result->getNotes());
        $this->assertNull($result->getMetadata());
    }

    public function testGetOrder(): void
    {
        $id = 'order_123';
        $order = new EcommerceOrder($id, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getOrder($id);
        $this->assertSame($order, $result);
    }

    public function testGetOrdersByBuyer(): void
    {
        $buyerId = 'buyer_123';
        $orders = [
            new EcommerceOrder('id1', $buyerId, [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD'),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['buyerId' => $buyerId], ['createdAt' => 'DESC'])
            ->willReturn($orders);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOrdersByBuyer($buyerId);
        $this->assertCount(1, $result);
    }

    public function testGetOrdersByStatus(): void
    {
        $status = 'CONFIRMED';
        $orders = [
            new EcommerceOrder('id1', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', $status),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['createdAt' => 'DESC'])
            ->willReturn($orders);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOrdersByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testGetPendingOrders(): void
    {
        $orders = [new EcommerceOrder('id', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'PENDING')];
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'PENDING'], ['createdAt' => 'DESC'])
            ->willReturn($orders);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getPendingOrders();
        $this->assertCount(1, $result);
    }

    public function testUpdateStatus(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'PENDING');
        $newStatus = 'CONFIRMED';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Order status updated', $this->anything());

        $result = $this->service->updateStatus($orderId, $newStatus);
        $this->assertSame($order, $result);
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateStatusNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateStatus('nonexistent', 'SOME');
        $this->assertNull($result);
    }

    public function testConfirmOrder(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'PENDING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->confirmOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('CONFIRMED', $result->getStatus());
    }

    public function testCancelOrder(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'CONFIRMED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cancelOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('CANCELLED', $result->getStatus());
    }

    public function testMarkShipped(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'CONFIRMED');
        $trackingNumber = 'TRK123';
        $carrier = 'DHL';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->markShipped($orderId, $trackingNumber, $carrier);
        $this->assertSame($order, $result);
        $this->assertSame('SHIPPED', $result->getStatus());
        $this->assertSame($trackingNumber, $result->getTrackingNumber());
        $this->assertSame($carrier, $result->getCarrier());
    }

    public function testMarkDelivered(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'SHIPPED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->markDelivered($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('DELIVERED', $result->getStatus());
    }

    public function testAddTrackingInfo(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD');
        $trackingNumber = 'TRK999';
        $carrier = 'FedEx';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->addTrackingInfo($orderId, $trackingNumber, $carrier);
        $this->assertSame($order, $result);
        $this->assertSame($trackingNumber, $result->getTrackingNumber());
        $this->assertSame($carrier, $result->getCarrier());
    }

    public function testGetOrdersByDateRange(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime();
        $orders = [new EcommerceOrder('id1', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.createdAt BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($orders);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getOrdersByDateRange($start, $end);
        $this->assertCount(1, $result);
    }

    public function testGetTotalSales(): void
    {
        $total = 12345.67;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('SUM(o.totalAmount)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.status IN (:statuses)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('statuses', ['CONFIRMED', 'SHIPPED', 'DELIVERED'])
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $total);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getTotalSales();
        $this->assertSame($total, $result);
    }

    public function testGetTotalSalesByBuyer(): void
    {
        $buyerId = 'buyer_123';
        $total = 500.00;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('SUM(o.totalAmount)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.buyerId = :buyerId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.status IN (:statuses)')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $total);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getTotalSalesByBuyer($buyerId);
        $this->assertSame($total, $result);
    }

    public function testGetAverageOrderValue(): void
    {
        $avg = 250.75;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('AVG(o.totalAmount)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.status IN (:statuses)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('statuses', ['CONFIRMED', 'SHIPPED', 'DELIVERED'])
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $avg);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getAverageOrderValue();
        $this->assertSame($avg, $result);
    }

    public function testGetOrderCountByStatus(): void
    {
        $counts = ['PENDING' => 10, 'CONFIRMED' => 5, 'CANCELLED' => 2];
        $rows = [
            ['status' => 'PENDING', 'cnt' => 10],
            ['status' => 'CONFIRMED', 'cnt' => 5],
            ['status' => 'CANCELLED', 'cnt' => 2],
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('o.status, COUNT(o.id) AS cnt')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('groupBy')
            ->with('o.status')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($rows);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getOrderCountByStatus();
        $this->assertSame($counts, $result);
    }

    public function testSearchByOrderIdPartial(): void
    {
        $partialId = 'abc123';
        $orders = [new EcommerceOrder('order_abc12345', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.id LIKE :partial')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('partial', '%' . $partialId . '%')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($orders);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->searchByOrderId($partialId);
        $this->assertCount(1, $result);
    }

    public function testRefundOrder(): void
    {
        $orderId = 'order_123';
        $order = new EcommerceOrder($orderId, 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'DELIVERED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->refundOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame('REFUNDED', $result->getStatus());
    }

    public function testGetRefundedOrders(): void
    {
        $orders = [new EcommerceOrder('id', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', 'CARD', 'REFUNDED')];
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'REFUNDED'])
            ->willReturn($orders);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getRefundedOrders();
        $this->assertCount(1, $result);
    }

    public function testGetOrdersByPaymentMethod(): void
    {
        $method = 'CREDIT_CARD';
        $orders = [new EcommerceOrder('id', 'buyer', [['price' => 10, 'quantity' => 1]], 10, 'addr', $method)];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['paymentMethod' => $method])
            ->willReturn($orders);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getOrdersByPaymentMethod($method);
        $this->assertCount(1, $result);
    }

    public function testGetHighValueOrders(): void
    {
        $threshold = 1000.00;
        $orders = [new EcommerceOrder('id', 'buyer', [['price' => 500, 'quantity' => 3]], 1500, 'addr', 'CARD')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('o.totalAmount > :threshold')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('threshold', $threshold)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($orders);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceOrder::class)
            ->willReturn($repo);

        $result = $this->service->getHighValueOrders($threshold);
        $this->assertCount(1, $result);
    }
}
