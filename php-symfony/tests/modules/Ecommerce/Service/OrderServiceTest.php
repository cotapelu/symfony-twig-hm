<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ecommerce\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceOrder;
use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Repository\EcommerceOrderRepository;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use App\Ecommerce\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

#[AllowMockObjectsWithoutExpectations]
class OrderServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EcommerceProductRepository&MockObject $productRepository;
    private EcommerceOrderRepository&MockObject $orderRepository;
    private IdempotencyKeyRepository&MockObject $idempotencyRepository;
    private LoggerInterface&MockObject $logger;
    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(EcommerceProductRepository::class);
        $this->orderRepository = $this->createMock(EcommerceOrderRepository::class);
        $this->idempotencyRepository = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new OrderService(
            $this->entityManager,
            $this->productRepository,
            $this->orderRepository,
            $this->idempotencyRepository,
            $this->logger
        );
    }

    public function testCreateOrderThrowsForNonPositiveQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        $this->service->createOrder('user-123', 'product-1', 0);
    }

    public function testCreateOrderThrowsWhenProductNotFound(): void
    {
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('product-1')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found');

        $this->service->createOrder('user-123', 'product-1', 1);
    }

    public function testCreateOrderThrowsWhenInsufficientStock(): void
    {
        $product = new EcommerceProduct();
        $product->setId('product-1');
        $product->setName('Test Product');
        $product->setPrice(10000);
        $product->setStock(5);
        $product->setCurrency('VND');

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('product-1')
            ->willReturn($product);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->createOrder('user-123', 'product-1', 10);
    }

    public function testCreateOrderSuccess(): void
    {
        $product = new EcommerceProduct();
        $product->setId('product-1');
        $product->setName('Test Product');
        $product->setPrice(10000);
        $product->setStock(100);
        $product->setCurrency('VND');

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('product-1')
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('decreaseStock')
            ->with('product-1', 2)
            ->willReturn(1); // affected rows

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceOrder $order) {
                return $order->getUserId() === 'user-123'
                    && $order->getProductId() === 'product-1'
                    && $order->getQuantity() === 2
                    && $order->getTotalPriceInCents() === 2000000
                    && $order->getStatus() === 'pending';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $order = $this->service->createOrder('user-123', 'product-1', 2);

        $this->assertInstanceOf(EcommerceOrder::class, $order);
        $this->assertSame('user-123', $order->getUserId());
        $this->assertSame('product-1', $order->getProductId());
        $this->assertSame(2, $order->getQuantity());
        $this->assertSame(20000.0, $order->getTotalPrice());
    }

    public function testCreateOrderWithShippingAddress(): void
    {
        $product = new EcommerceProduct();
        $product->setId('product-1');
        $product->setName('Test Product');
        $product->setPrice(10000);
        $product->setStock(100);
        $product->setCurrency('VND');

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('product-1')
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('decreaseStock')
            ->with('product-1', 1)
            ->willReturn(1);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceOrder $order) {
                $address = $order->getShippingAddress();
                return is_array($address)
                    && $address['street'] === '123 Main St'
                    && $address['city'] === 'Hanoi';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $shippingAddress = json_encode([
            'street' => '123 Main St',
            'city' => 'Hanoi'
        ]);

        $order = $this->service->createOrder('user-123', 'product-1', 1, $shippingAddress);

        $this->assertInstanceOf(EcommerceOrder::class, $order);
        $this->assertIsArray($order->getShippingAddress());
        $this->assertSame('123 Main St', $order->getShippingAddress()['street']);
    }

    public function testCreateOrderReturnsExistingWhenIdempotencyKeyMatches(): void
    {
        $product = new EcommerceProduct();
        $product->setId('product-1');
        $product->setName('Test Product');
        $product->setPrice(10000);
        $product->setStock(100);
        $product->setCurrency('VND');

        $existingOrder = new EcommerceOrder();
        $existingOrder->setId('order-1');
        $existingOrder->setUserId('user-123');
        $existingOrder->setProductId('product-1');
        $existingOrder->setQuantity(1);
        $existingOrder->setTotalPrice(10000);
        $existingOrder->setStatus('pending');

        $idempotencyKey = new IdempotencyKey(
            'idem-key-123',
            'EcommerceOrder',
            'order-1',
            new \DateTime('+24 hours')
        );

        $this->idempotencyRepository->expects($this->once())
            ->method('findByKey')
            ->with('idem-key-123')
            ->willReturn($idempotencyKey);

        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with('order-1')
            ->willReturn($existingOrder);

        // Should NOT call product repository or begin transaction
        $this->productRepository->expects($this->never())
            ->method('findById');

        $this->entityManager->expects($this->never())
            ->method('beginTransaction');

        $order = $this->service->createOrder('user-123', 'product-1', 1, null, 'idem-key-123');

        $this->assertSame($existingOrder, $order);
    }

    public function testCreateOrderWithStockConcurrencyRetry(): void
    {
        $product = new EcommerceProduct();
        $product->setId('product-1');
        $product->setName('Test Product');
        $product->setPrice(10000);
        $product->setStock(10);
        $product->setCurrency('VND');

        // Service throws exception when decreaseStock returns 0 (no retry logic)
        // findById is called once before decreaseStock, then exception is thrown
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('product-1')
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('decreaseStock')
            ->with('product-1', 2)
            ->willReturn(0);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock concurrently updated. Please retry.');

        $this->service->createOrder('user-123', 'product-1', 2);
    }
}
