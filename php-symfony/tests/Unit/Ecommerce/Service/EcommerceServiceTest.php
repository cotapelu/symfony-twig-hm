<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Service\EcommerceService;
use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class EcommerceServiceTest extends TestCase
{
    private EcommerceProductRepository&MockObject $productRepo;
    private OrderService&MockObject $orderService;
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private EcommerceService $service;

    protected function setUp(): void
    {
        $this->productRepo = $this->createMock(EcommerceProductRepository::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new EcommerceService(
            $this->productRepo,
            $this->orderService,
            $this->em,
            $this->logger
        );
    }

    public function testCreateProductValidatesRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createProduct(['name' => 'Test']);
    }

    public function testCreateProductSuccess(): void
    {
        $data = [
            'seller_id' => 'seller-1',
            'name' => 'Test Product',
            'price' => 19.99,
            'description' => 'A test product',
            'category' => 'electronics',
            'stock' => 100,
        ];
        $this->productRepo->expects($this->once())->method('save');
        $product = $this->service->createProduct($data);
        $this->assertInstanceOf(EcommerceProduct::class, $product);
        $this->assertEquals('seller-1', $product->getSellerId());
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(19.99, $product->getPrice());
        $this->assertEquals(100, $product->getStock());
        $this->assertTrue($product->getIsActive()); // default true
    }

    public function testGetProductReturnsRepositoryResult(): void
    {
        $product = new EcommerceProduct();
        $product->setId('p1');
        $product->setSellerId('s1');
        $product->setName('P1');
        $this->productRepo->expects($this->once())->method('findById')->with('p1')->willReturn($product);
        $result = $this->service->getProduct('p1');
        $this->assertSame($product, $result);
    }

    public function testGetSellerProducts(): void
    {
        $products = [$this->createProduct('s1', 'P1')];
        $this->productRepo->expects($this->once())->method('findBySeller')->with('s1', 20, 0)->willReturn($products);
        $result = $this->service->getSellerProducts('s1');
        $this->assertSame($products, $result);
    }

    public function testGetProductsByCategory(): void
    {
        $products = [$this->createProduct('s1', 'P1')];
        $this->productRepo->expects($this->once())->method('findByCategory')->with('books', 10, 5)->willReturn($products);
        $result = $this->service->getProductsByCategory('books', 10, 5);
        $this->assertSame($products, $result);
    }

    public function testSearchProducts(): void
    {
        $products = [$this->createProduct('s1', 'Search Result')];
        $this->productRepo->expects($this->once())->method('searchByName')->with('query', 15, 0)->willReturn($products);
        $result = $this->service->searchProducts('query', 15);
        $this->assertSame($products, $result);
    }

    public function testGetProducts(): void
    {
        $products = [$this->createProduct('s1', 'Active')];
        $this->productRepo->expects($this->once())->method('findActive')->with(30, 5)->willReturn($products);
        $result = $this->service->getProducts(30, 5);
        $this->assertSame($products, $result);
    }

    public function testCountActiveProducts(): void
    {
        $this->productRepo->expects($this->once())->method('countActive')->willReturn(42);
        $result = $this->service->countActiveProducts();
        $this->assertEquals(42, $result);
    }

    public function testDeleteProductReturnsFalseWhenNotFound(): void
    {
        $this->em->expects($this->once())->method('find')->with(EcommerceProduct::class, 'nonexistent')->willReturn(null);
        $result = $this->service->deleteProduct('nonexistent');
        $this->assertFalse($result);
    }

    public function testDeleteProductChecksOwnershipAndRemoves(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('owner1');
        $this->em->expects($this->once())->method('find')->with(EcommerceProduct::class, 'p1')->willReturn($product);
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->deleteProduct('p1', 'owner1');
        $this->assertTrue($result);
    }

    public function testDeleteProductThrowsWhenNotOwner(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('owner1');
        $this->em->method('find')->willReturn($product);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');
        $this->service->deleteProduct('p1', 'other');
    }

    public function testActivateProduct(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('s1');
        $product->expects($this->once())->method('setIsActive')->with(true);
        $product->expects($this->once())->method('setUpdatedAt');
        $this->productRepo->expects($this->once())->method('findById')->with('p1')->willReturn($product);
        $this->productRepo->expects($this->once())->method('save');
        $result = $this->service->activateProduct('p1', 's1');
        $this->assertSame($product, $result);
    }

    public function testDeactivateProduct(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('s1');
        $product->expects($this->once())->method('setIsActive')->with(false);
        $product->expects($this->once())->method('setUpdatedAt');
        $this->productRepo->expects($this->once())->method('findById')->with('p1')->willReturn($product);
        $this->productRepo->expects($this->once())->method('save');
        $result = $this->service->deactivateProduct('p1', 's1');
        $this->assertSame($product, $result);
    }

    public function testUpdateProductSuccess(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('s1');
        $product->expects($this->once())->method('setName')->with('Updated Name');
        $product->expects($this->once())->method('setPrice')->with(29.99);
        $product->expects($this->once())->method('setUpdatedAt');
        $this->productRepo->expects($this->once())->method('findById')->with('p1')->willReturn($product);
        $this->productRepo->expects($this->once())->method('save');
        $data = ['name' => 'Updated Name', 'price' => 29.99];
        $result = $this->service->updateProduct('p1', $data);
        $this->assertSame($product, $result);
    }

    public function testUpdateProductChecksOwnership(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('s1');
        $this->productRepo->method('findById')->willReturn($product);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateProduct('p1', ['seller_id' => 'different']);
    }

    public function testUpdateStock(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getSellerId')->willReturn('s1');
        $product->expects($this->once())->method('setStock')->with(50);
        $product->expects($this->once())->method('setUpdatedAt');
        $this->productRepo->expects($this->once())->method('findById')->with('p1')->willReturn($product);
        $this->productRepo->expects($this->once())->method('save');
        $result = $this->service->updateStock('p1', 50, 's1');
        $this->assertSame($product, $result);
    }

    public function testGetFeaturedProducts(): void
    {
        $p1 = $this->createProduct('s1', 'Expensive', 200);
        $p2 = $this->createProduct('s2', 'Cheap', 50);
        $p3 = $this->createProduct('s3', 'Mid', 100);
        // Repository returns all active; service will sort by price descending
        $this->productRepo->expects($this->once())->method('findActive')->with(4, 0)->willReturn([$p1, $p2, $p3]);
        $result = $this->service->getFeaturedProducts(2);
        $this->assertCount(2, $result);
        $this->assertSame($p1, $result[0]); // highest price
        $this->assertSame($p3, $result[1]); // next highest
    }

    public function testCreateOrderDelegates(): void
    {
        $this->orderService->expects($this->once())
            ->method('createOrder')
            ->with('user1', 'prod1', 2, null, null)
            ->willReturn(['orderId' => 'ord1']);
        $result = $this->service->createOrder([
            'user_id' => 'user1',
            'product_id' => 'prod1',
            'quantity' => 2,
        ]);
        $this->assertEquals(['orderId' => 'ord1'], $result);
    }

    public function testGetOrderDelegates(): void
    {
        $this->orderService->expects($this->once())->method('getOrder')->with('ord1', 'user1')->willReturn(['order']);
        $result = $this->service->getOrder('ord1', 'user1');
        $this->assertEquals(['order'], $result);
    }

    public function testConfirmOrderDelegates(): void
    {
        $this->orderService->expects($this->once())->method('confirmOrder')->with('ord1', 'user1')->willReturn(['confirmed']);
        $result = $this->service->confirmOrder('ord1', 'user1');
        $this->assertEquals(['confirmed'], $result);
    }

    public function testCancelOrderDelegates(): void
    {
        $this->orderService->expects($this->once())->method('cancelOrder')->with('ord1', 'user1')->willReturn(['cancelled']);
        $result = $this->service->cancelOrder('ord1', 'user1');
        $this->assertEquals(['cancelled'], $result);
    }

    public function testGetUserOrdersDelegates(): void
    {
        $this->orderService->expects($this->once())->method('getUserOrders')->with('user1', 10)->willReturn(['orders']);
        $result = $this->service->getUserOrders('user1', 10);
        $this->assertEquals(['orders'], $result);
    }

    // Helper to create a product with given sellerId and name; optional price
    private function createProduct(string $sellerId, string $name, float $price = 100.0): EcommerceProduct
    {
        $product = new EcommerceProduct();
        $product->setId((string) \Ramsey\Uuid\Uuid::uuid4());
        $product->setSellerId($sellerId);
        $product->setName($name);
        $product->setPrice($price);
        $product->setIsActive(true);
        return $product;
    }
}
