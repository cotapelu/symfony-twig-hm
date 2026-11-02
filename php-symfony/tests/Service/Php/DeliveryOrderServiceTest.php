<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use App\Service\Php\DeliveryOrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryOrderRepository&MockObject $repository;
    private DeliveryOrderService $service;

    private string $orderId;
    private DeliveryOrder $order;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryOrderRepository::class);
        $this->service = new DeliveryOrderService($this->em, $this->repository);

        $this->orderId = bin2hex(random_bytes(16));
        $this->order = new DeliveryOrder(
            $this->orderId,
            'store-1',
            'customer-1',
            'DELIVERY',
            5000
        );
    }

    public function testCreateDeliveryOrderSuccess(): void
    {
        $storeId = 'store-1';
        $customerId = 'cust-1';
        $type = 'PICKUP';
        $totalCents = 2500;
        $deliveryAddress = ['address' => '123 St'];
        $deliveryPersonId = 'dp-1';
        $status = 'CONFIRMED';
        $metadata = ['priority' => true];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryOrder $o) use (&$captured, $storeId, $customerId, $type, $totalCents, $deliveryAddress, $deliveryPersonId, $status, $metadata) {
                $captured = $o;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryOrder($storeId, $customerId, $type, $totalCents, $deliveryAddress, $deliveryPersonId, $status, $metadata);
        $this->assertInstanceOf(DeliveryOrder::class, $result);
        $this->assertSame($storeId, $result->getStoreId());
        $this->assertSame($customerId, $result->getCustomerId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($totalCents, $result->getTotalCents());
        $this->assertSame($deliveryAddress, $result->getDeliveryAddress());
        $this->assertSame($deliveryPersonId, $result->getDeliveryPersonId());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateDeliveryOrderValidationEmptyStoreId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Store ID is required');
        $this->service->createDeliveryOrder('', 'cust', 'type', 100);
    }

    public function testCreateDeliveryOrderValidationEmptyCustomerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID is required');
        $this->service->createDeliveryOrder('store', '', 'type', 100);
    }

    public function testCreateDeliveryOrderValidationEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order type is required');
        $this->service->createDeliveryOrder('store', 'cust', '', 100);
    }

    public function testCreateDeliveryOrderValidationInvalidTotal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total must be positive');
        $this->service->createDeliveryOrder('store', 'cust', 'type', 0);
    }

    public function testGetDeliveryOrderSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $result = $this->service->getDeliveryOrder($this->orderId);
        $this->assertSame($this->order, $result);
    }

    public function testGetDeliveryOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryOrder not found');
        $this->service->getDeliveryOrder($this->orderId);
    }

    public function testUpdateDeliveryOrderSuccess(): void
    {
        $newStoreId = 'store-2';
        $newCustomerId = 'cust-2';
        $newType = 'PICKUP';
        $newTotal = 3000;
        $newDeliveryAddress = ['city' => 'Hanoi'];
        $newDeliveryPerson = 'dp-2';
        $newStatus = 'DELIVERED';
        $newMetadata = ['note' => 'fragile'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'storeId' => $newStoreId,
            'customerId' => $newCustomerId,
            'type' => $newType,
            'totalCents' => $newTotal,
            'deliveryAddress' => $newDeliveryAddress,
            'deliveryPersonId' => $newDeliveryPerson,
            'status' => $newStatus,
            'metadata' => $newMetadata,
        ];
        $result = $this->service->updateDeliveryOrder($this->orderId, $data);
        $this->assertSame($this->order, $result);
        $this->assertSame($newStoreId, $result->getStoreId());
        $this->assertSame($newCustomerId, $result->getCustomerId());
        $this->assertSame($newType, $result->getType());
        $this->assertSame($newTotal, $result->getTotalCents());
        $this->assertSame($newDeliveryAddress, $result->getDeliveryAddress());
        $this->assertSame($newDeliveryPerson, $result->getDeliveryPersonId());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateDeliveryOrderValidationEmptyStoreId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Store ID cannot be empty');
        $this->service->updateDeliveryOrder($this->orderId, ['storeId' => '']);
    }

    public function testUpdateDeliveryOrderValidationEmptyCustomerId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID cannot be empty');
        $this->service->updateDeliveryOrder($this->orderId, ['customerId' => '']);
    }

    public function testUpdateDeliveryOrderValidationEmptyType(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order type cannot be empty');
        $this->service->updateDeliveryOrder($this->orderId, ['type' => '']);
    }

    public function testUpdateDeliveryOrderValidationInvalidTotal(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total must be positive');
        $this->service->updateDeliveryOrder($this->orderId, ['totalCents' => -10]);
    }

    public function testUpdateDeliveryOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryOrder($this->orderId, []);
    }

    public function testDeleteDeliveryOrderSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);
        $this->em->expects($this->once())->method('remove')->with($this->order);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryOrder($this->orderId);
    }

    public function testDeleteDeliveryOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteDeliveryOrder($this->orderId);
    }

    public function testFindByStoreId(): void
    {
        $storeId = 'store-1';
        $results = [$this->order];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['storeId' => $storeId])
            ->willReturn($results);

        $result = $this->service->findByStoreId($storeId);
        $this->assertSame($results, $result);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = 'cust-1';
        $results = [$this->order];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['customerId' => $customerId])
            ->willReturn($results);

        $result = $this->service->findByCustomerId($customerId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'COMPLETED';
        $results = [$this->order];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testFindByDeliveryPersonId(): void
    {
        $dpId = 'dp-1';
        $results = [$this->order];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['deliveryPersonId' => $dpId])
            ->willReturn($results);

        $result = $this->service->findByDeliveryPersonId($dpId);
        $this->assertSame($results, $result);
    }
}
