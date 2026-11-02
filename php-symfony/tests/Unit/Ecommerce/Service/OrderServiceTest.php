<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Entity\EcommerceOrder;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Repository\EcommerceOrderRepository;
use App\Ecommerce\Service\OrderService;
use App\DigitalWallet\Entity\IdempotencyKey;
use App\DigitalWallet\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class OrderServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EcommerceProductRepository $productRepository;
    private EcommerceOrderRepository $orderRepository;
    private IdempotencyKeyRepository $idempotencyRepository;
    private LoggerInterface $logger;
    private OrderService $service;

    protected function setUp(): void
    {
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

    public function testCreateOrderThrowsWhenQuantityInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');
        $this->service->createOrder('user_123', 'prod_123', 0);
    }

    public function testCreateOrderThrowsWhenProductNotFound(): void
    {
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('prod_123')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found');

        $this->service->createOrder('user_123', 'prod_123', 1);
    }

    public function testCreateOrderThrowsWhenInsufficientStock(): void
    {
        $product = new EcommerceProduct();
        $product->setId('prod_123');
        $product->setPrice(10.0);
        $product->setCurrency('USD');
        $product->setStock(5); // only 5 in stock

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('prod_123')
            ->willReturn($product);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->createOrder('user_123', 'prod_123', 10); // request 10
    }

    public function testCreateOrderSuccess(): void
    {
        $userId = 'user_123';
        $productId = 'prod_123';
        $quantity = 2;
        $shippingAddress = null;

        // Create a real product with enough stock
        $product = new EcommerceProduct();
        $product->setId($productId);
        $product->setPrice(19.99);
        $product->setCurrency('USD');
        $product->setStock(100);

        // Setup product repository
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        // Expect decreaseStock to be called
        $this->productRepository->expects($this->once())
            ->method('decreaseStock')
            ->with($productId, $quantity)
            ->willReturn(1); // affected rows

        // Idempotency: no key, so repository never called
        $this->idempotencyRepository->expects($this->never())->method('findByKey');

        // Transaction
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('commit');

        // Persist and flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceOrder $order) use ($userId, $productId, $quantity, $product) {
                return $order->getUserId() === $userId
                    && $order->getProductId() === $productId
                    && $order->getQuantity() === $quantity
                    && $order->getTotalPrice() === $product->getPrice() * $quantity
                    && $order->getCurrency() === $product->getCurrency()
                    && $order->getStatus() === 'pending'
                    && $order->getShippingAddress() === null;
            }));
        $this->entityManager->expects($this->once())->method('flush');

        // Logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Order created', $this->arrayHasKey('order_id'));

        $order = $this->service->createOrder($userId, $productId, $quantity, $shippingAddress);

        $this->assertInstanceOf(EcommerceOrder::class, $order);
        $this->assertSame($userId, $order->getUserId());
        $this->assertSame($productId, $order->getProductId());
        $this->assertSame($quantity, $order->getQuantity());
        $this->assertEquals($product->getPrice() * $quantity, $order->getTotalPrice());
        $this->assertSame('USD', $order->getCurrency());
        $this->assertSame('pending', $order->getStatus());
    }

    public function testCreateOrderIdempotencyReturnsExistingOrder(): void
    {
        $userId = 'user_123';
        $productId = 'prod_123';
        $quantity = 2;
        $idempotencyKey = 'key_123';
        $existingOrderId = 'order_456';

        // Mock idempotency key repository to return a key
        $idempKeyMock = $this->createMock(IdempotencyKey::class);
        $idempKeyMock->method('isExpired')->willReturn(false);
        $idempKeyMock->method('getEntityType')->willReturn('EcommerceOrder');
        $idempKeyMock->method('getEntityId')->willReturn($existingOrderId);

        $this->idempotencyRepository->expects($this->once())
            ->method('findByKey')
            ->with($idempotencyKey)
            ->willReturn($idempKeyMock);

        // Existing order returned by order repository find
        $existingOrder = $this->createMock(EcommerceOrder::class);
        $existingOrder->method('getId')->willReturn($existingOrderId);
        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with($existingOrderId)
            ->willReturn($existingOrder);

        // Ensure product repository is not called
        $this->productRepository->expects($this->never())->method('findById');
        $this->entityManager->expects($this->never())->method('beginTransaction');
        $this->logger->expects($this->never())->method('info');

        $result = $this->service->createOrder($userId, $productId, $quantity, null, $idempotencyKey);

        $this->assertSame($existingOrder, $result);
    }
}
