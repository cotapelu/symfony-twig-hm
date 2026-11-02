<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\DeliveryOrderItem;
use App\Service\Php\DeliveryOrderItemService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeliveryOrderItemServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private DeliveryOrderItemService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new DeliveryOrderItemService($this->em, $this->logger);
    }

    public function testCreateSuccess(): void
    {
        $orderId = 'order123';
        $productId = 'product456';
        $quantity = 2;
        $priceCents = 1999;

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryOrderItem::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Delivery order item created', $this->anything());

        $result = $this->service->create($orderId, $productId, $quantity, $priceCents);
        $this->assertInstanceOf(DeliveryOrderItem::class, $result);
        $this->assertSame($orderId, $result->getOrderId());
        $this->assertSame($productId, $result->getProductId());
        $this->assertSame($quantity, $result->getQuantity());
        $this->assertSame($priceCents, $result->getPriceCents());
    }

    public function testCreateFailsValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create('', 'product', 1, 100);
    }

    public function testCreateFailsQuantityZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create('order', 'product', 0, 100);
    }

    public function testGetSuccess(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $item = new DeliveryOrderItem($id, 'order', 'product', 1, 100);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($item);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryOrderItem::class)
            ->willReturn($repo);

        $result = $this->service->get($id);
        $this->assertSame($item, $result);
    }

    public function testGetByOrderSuccess(): void
    {
        $orderId = 'order123';
        $item1 = new DeliveryOrderItem('id1', $orderId, 'p1', 1, 100);
        $item2 = new DeliveryOrderItem('id2', $orderId, 'p2', 2, 200);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['orderId' => $orderId])
            ->willReturn([$item1, $item2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getByOrder($orderId);
        $this->assertCount(2, $result);
    }

    public function testUpdateQuantitySuccess(): void
    {
        $id = 'id123';
        $newQty = 5;
        $item = new DeliveryOrderItem($id, 'order', 'product', 1, 100);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($item);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateQuantity($id, $newQty);
        $this->assertSame($item, $result);
        $this->assertSame($newQty, $result->getQuantity());
    }

    public function testUpdateQuantityFailsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateQuantity('id', 0);
    }

    public function testDeleteSuccess(): void
    {
        $id = 'id123';
        $item = new DeliveryOrderItem($id, 'order', 'product', 1, 100);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($item);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($item);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testGetTotalQuantity(): void
    {
        $orderId = 'order123';
        $total = 10;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('SUM(i.quantity)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('i.orderId = :orderId')
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
            ->with('i')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryOrderItem::class)
            ->willReturn($repo);

        $result = $this->service->getTotalQuantity($orderId);
        $this->assertSame($total, $result);
    }
}
