<?php

namespace App\Tests\Ecommerce\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Service\EcommerceService;
use App\Ecommerce\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PhpUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class EcommerceServiceTest extends TestCase
{
    private MockObject $productRepository;
    private MockObject $orderService;
    private MockObject $entityManager;
    private MockObject $logger;

    private EcommerceService $service;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(EcommerceProductRepository::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new EcommerceService(
            $this->productRepository,
            $this->orderService,
            $this->entityManager,
            $this->logger
        );
    }

    public function testCreateProductSavesToRepository(): void
    {
        $data = [
            'seller_id' => 'user-123',
            'name' => 'Test Product',
            'price' => 29.99,
            'category' => 'electronics',
            'description' => 'A test product',
            'stock' => 100,
        ];

        $product = new EcommerceProduct();
        $product->setSellerId($data['seller_id']);
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setCategory($data['category']);
        $product->setDescription($data['description']);
        $product->setStock($data['stock']);

        $this->productRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (EcommerceProduct $p) use ($data) {
                return $p->getSellerId() === $data['seller_id']
                    && $p->getName() === $data['name']
                    && $p->getPrice() === $data['price']
                    && $p->getCategory() === $data['category']
                    && $p->getDescription() === $data['description']
                    && $p->getStock() === $data['stock'];
            }))
            ->willReturnArgument(0);

        $result = $this->service->createProduct($data);
        $this->assertInstanceOf(EcommerceProduct::class, $result);
        $this->assertSame($data['seller_id'], $result->getSellerId());
        $this->assertSame($data['name'], $result->getName());
        $this->assertSame($data['price'], $result->getPrice());
    }

    public function testGetProductReturnsFromRepository(): void
    {
        $productId = 'prod-123';
        $expectedProduct = new EcommerceProduct();

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($expectedProduct);

        $result = $this->service->getProduct($productId);
        $this->assertSame($expectedProduct, $result);
    }

    public function testGetProductReturnsNullWhenNotFound(): void
    {
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->getProduct('nonexistent');
        $this->assertNull($result);
    }

    public function testGetSellerProductsUsesRepository(): void
    {
        $sellerId = 'seller-456';
        $limit = 10;
        $offset = 0;
        $products = [new EcommerceProduct(), new EcommerceProduct()];

        $this->productRepository->expects($this->once())
            ->method('findBySeller')
            ->with($sellerId, $limit, $offset)
            ->willReturn($products);

        $result = $this->service->getSellerProducts($sellerId, $limit, $offset);
        $this->assertSame($products, $result);
    }

    public function testDeleteProductRemovesFromDatabase(): void
    {
        $productId = 'prod-delete';
        $product = new EcommerceProduct();
        $product->setId($productId);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(EcommerceProduct::class, $productId)
            ->willReturn($product);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($product);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteProduct($productId);
        $this->assertTrue($result);
    }

    public function testDeleteProductReturnsFalseWhenNotFound(): void
    {
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(EcommerceProduct::class, 'notfound')
            ->willReturn(null);

        // EntityManager should not be called
        $this->entityManager->expects($this->never())
            ->method('remove');

        $result = $this->service->deleteProduct('notfound');
        $this->assertFalse($result);
    }

    public function testUpdateProductChangesFields(): void
    {
        $productId = 'prod-update';
        $product = new EcommerceProduct();
        $product->setId($productId);
        $product->setName('Old Name');
        $product->setStock(10);

        $updateData = [
            'name' => 'New Name',
            'stock' => 50,
            'description' => 'New desc',
        ];

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (EcommerceProduct $p) use ($updateData) {
                return $p->getName() === $updateData['name']
                    && $p->getStock() === $updateData['stock']
                    && $p->getDescription() === $updateData['description'];
            }));

        $result = $this->service->updateProduct($productId, $updateData);
        $this->assertInstanceOf(EcommerceProduct::class, $result);
        $this->assertSame($updateData['name'], $result->getName());
        $this->assertSame($updateData['stock'], $result->getStock());
    }

    public function testUpdateProductReturnsNullIfNotFound(): void
    {
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->service->updateProduct('notfound', []);
        $this->assertNull($result);
    }

    public function testCountActiveProductsDelegatesToRepository(): void
    {
        $this->productRepository->expects($this->once())
            ->method('countActive')
            ->willReturn(42);

        $result = $this->service->countActiveProducts();
        $this->assertSame(42, $result);
    }

    public function testGetFeaturedProductsSortedByPriceDesc(): void
    {
        $p1 = new EcommerceProduct(); $p1->setPrice(100);
        $p2 = new EcommerceProduct(); $p2->setPrice(50);
        $p3 = new EcommerceProduct(); $p3->setPrice(75);

        $this->productRepository->expects($this->once())
            ->method('findActive')
            ->with(20) // limit*2
            ->willReturn([$p1, $p2, $p3]);

        $result = $this->service->getFeaturedProducts(10);
        $this->assertCount(3, $result);
        $this->assertSame($p1, $result[0]); // highest first
        $this->assertSame($p3, $result[1]); // middle
        $this->assertSame($p2, $result[2]); // lowest
    }

    public function testCreateOrderDelegatesToOrderService(): void
    {
        $orderData = [
            'user_id' => 'user-1',
            'product_id' => 'prod-1',
            'quantity' => 2,
            'shipping_address' => json_encode(['addr' => '123 Main']),
            'idempotency_key' => 'key-123',
        ];

        $mockOrder = new \stdClass(); // doesn't matter

        $this->orderService->expects($this->once())
            ->method('createOrder')
            ->with(
                $orderData['user_id'],
                $orderData['product_id'],
                $orderData['quantity'],
                $orderData['shipping_address'],
                $orderData['idempotency_key']
            )
            ->willReturn($mockOrder);

        $result = $this->service->createOrder($orderData);
        $this->assertSame($mockOrder, $result);
    }

    public function testOrderMethodsDelegateToOrderService(): void
    {
        $orderId = 'order-123';
        $userId = 'user-123';

        $this->orderService->expects($this->once())
            ->method('getOrder')
            ->with($orderId, $userId)
            ->willReturn(new \stdClass());

        $result = $this->service->getOrder($orderId, $userId);
        $this->assertIsObject($result);

        $this->orderService->expects($this->once())
            ->method('confirmOrder')
            ->with($orderId, $userId)
            ->willReturn(new \stdClass());

        $result = $this->service->confirmOrder($orderId, $userId);
        $this->assertIsObject($result);

        $this->orderService->expects($this->once())
            ->method('cancelOrder')
            ->with($orderId, $userId)
            ->willReturn(new \stdClass());

        $result = $this->service->cancelOrder($orderId, $userId);
        $this->assertIsObject($result);

        $this->orderService->expects($this->once())
            ->method('getUserOrders')
            ->with($userId, 50)
            ->willReturn([]);

        $result = $this->service->getUserOrders($userId, 50);
        $this->assertIsArray($result);
    }
}
