<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceOrder;
use App\Repository\Php\EcommerceOrderRepository;
use App\Service\Php\EcommerceOrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceOrderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EcommerceOrderRepository&MockObject $repository;
    private EcommerceOrderService $service;

    private string $orderId;
    private EcommerceOrder $order;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EcommerceOrderRepository::class);
        $this->service = new EcommerceOrderService($this->em, $this->repository);

        $this->orderId = bin2hex(random_bytes(16));
        $this->order = new EcommerceOrder($this->orderId, 'customer-1', 5000);
        $this->order->setCurrency('USD');
    }

    public function testCreateOrderSuccess(): void
    {
        $customerId = 'cust-123';
        $totalCents = 2500;
        $currency = 'EUR';
        $shippingAddress = ['city' => 'Paris'];
        $billingAddress = ['city' => 'Berlin'];
        $metadata = ['gift' => true];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceOrder $o) use (&$captured, $customerId, $totalCents, $currency, $shippingAddress, $billingAddress, $metadata) {
                $captured = $o;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createOrder($customerId, $totalCents, $currency, $shippingAddress, $billingAddress, $metadata);
        $this->assertInstanceOf(EcommerceOrder::class, $result);
        $this->assertSame($customerId, $result->getCustomerId());
        $this->assertSame($totalCents, $result->getTotalCents());
        $this->assertSame($currency, $result->getCurrency());
        $this->assertSame($shippingAddress, $result->getShippingAddress());
        $this->assertSame($billingAddress, $result->getBillingAddress());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateOrderValidationEmptyCustomerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID is required');
        $this->service->createOrder('', 100);
    }

    public function testCreateOrderValidationNonPositiveTotal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total must be positive');
        $this->service->createOrder('cust', 0);
    }

    public function testCreateOrderValidationEmptyCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency cannot be empty');
        $this->service->createOrder('cust', 100, '   ');
    }

    public function testGetOrderSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $result = $this->service->getOrder($this->orderId);
        $this->assertSame($this->order, $result);
    }

    public function testGetOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('EcommerceOrder not found');
        $this->service->getOrder($this->orderId);
    }

    public function testUpdateOrderSuccess(): void
    {
        $newCustomerId = 'cust-2';
        $newTotal = 3000;
        $newStatus = 'SHIPPED';
        $newCurrency = 'USD';
        $newShipping = ['country' => 'VN'];
        $newBilling = ['bank' => 'Techcombank'];
        $newMetadata = ['promo' => 'summer'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'customerId' => $newCustomerId,
            'totalCents' => $newTotal,
            'status' => $newStatus,
            'currency' => $newCurrency,
            'shippingAddress' => $newShipping,
            'billingAddress' => $newBilling,
            'metadata' => $newMetadata,
        ];
        $result = $this->service->updateOrder($this->orderId, $data);
        $this->assertSame($this->order, $result);
        $this->assertSame($newCustomerId, $result->getCustomerId());
        $this->assertSame($newTotal, $result->getTotalCents());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newCurrency, $result->getCurrency());
        $this->assertSame($newShipping, $result->getShippingAddress());
        $this->assertSame($newBilling, $result->getBillingAddress());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateOrderValidationEmptyCustomerId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID cannot be empty');
        $this->service->updateOrder($this->orderId, ['customerId' => '']);
    }

    public function testUpdateOrderValidationNonPositiveTotal(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total must be positive');
        $this->service->updateOrder($this->orderId, ['totalCents' => -10]);
    }

    public function testUpdateOrderValidationEmptyCurrency(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency cannot be empty');
        $this->service->updateOrder($this->orderId, ['currency' => '   ']);
    }

    public function testUpdateOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateOrder($this->orderId, []);
    }

    public function testDeleteOrderSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn($this->order);
        $this->em->expects($this->once())->method('remove')->with($this->order);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteOrder($this->orderId);
    }

    public function testDeleteOrderNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->orderId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteOrder($this->orderId);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = 'cust-123';
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
        $status = 'CANCELLED';
        $results = [$this->order];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
