<?php

declare(strict_types=1);

namespace App\Tests\Controller\Ecommerce;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Controller\EcommerceController;
use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Entity\EcommerceOrder as Order;
use App\Ecommerce\Service\EcommerceService;
use App\Ecommerce\Service\OrderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class EcommerceControllerTest extends TestCase
{
    private EcommerceController $controller;
    private EcommerceService|MockObject $ecommerceService;
    private OrderService|MockObject $orderService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->ecommerceService = $this->createMock(EcommerceService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn('seller123');

        $this->controller = new EcommerceController($this->ecommerceService, $this->orderService);
        $this->setTestUser();
    }

    private function setTestUser(): void
    {
        $this->controller->setTestUser($this->currentUser);
    }

    public function testGetProducts(): void
    {
        $request = new Request(['limit' => '10']);

        $products = [
            ['id' => 'product1', 'name' => 'Product A', 'price' => 100000],
            ['id' => 'product2', 'name' => 'Product B', 'price' => 200000],
        ];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getProducts')
            ->with(10)
            ->willReturn($products);

        $result = $this->controller->getProducts($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetProductsDefaultLimit(): void
    {
        $request = new Request();
        $products = [];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getProducts')
            ->with(20)
            ->willReturn($products);

        $result = $this->controller->getProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetProductSuccess(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('toArray')->willReturn([
            'id' => 'product123',
            'name' => 'Test Product',
            'price' => 150000,
        ]);

        $this->ecommerceService
            ->expects($this->once())
            ->method('getProduct')
            ->with('product123')
            ->willReturn($product);

        $result = $this->controller->getProduct('product123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('product123', $data['id']);
        $this->assertEquals('Test Product', $data['name']);
    }

    public function testGetProductNotFound(): void
    {
        $this->ecommerceService
            ->expects($this->once())
            ->method('getProduct')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->getProduct('notfound');

        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Product not found', $data['error']);
    }

    public function testCreateProductSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Product',
                'price' => 100000,
                'description' => 'Product description',
            ])
        );

        $product = $this->createMock(EcommerceProduct::class);
        $product->method('toArray')->willReturn([
            'id' => 'product123',
            'name' => 'New Product',
            'price' => 100000,
        ]);

        $this->ecommerceService
            ->expects($this->once())
            ->method('createProduct')
            ->with([
                'seller_id' => 'seller123',
                'name' => 'New Product',
                'price' => 100000,
                'description' => 'Product description',
            ])
            ->willReturn($product);

        $result = $this->controller->createProduct($request);

        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('product123', $data['id']);
    }

    public function testCreateProductInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->createProduct($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreateProductMissingFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test'])
        );

        $result = $this->controller->createProduct($request);

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Name and price are required', $data['error']);
    }

    public function testUpdateProductSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Product',
                'price' => 150000,
            ])
        );

        $this->ecommerceService
            ->expects($this->once())
            ->method('updateProduct')
            ->with('product123', [
                'seller_id' => 'seller123',
                'name' => 'Updated Product',
                'price' => 150000,
            ]);

        $result = $this->controller->updateProduct($request, 'product123');

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Product updated successfully', $data['message']);
    }

    public function testUpdateProductInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->updateProduct($request, 'product123');

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testUpdateProductUnauthorized(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated'])
        );

        $this->ecommerceService
            ->expects($this->once())
            ->method('updateProduct')
            ->willThrowException(new \InvalidArgumentException('Not authorized to update this product'));

        $result = $this->controller->updateProduct($request, 'product123');

        $this->assertEquals(403, $result->getStatusCode());
    }

    public function testCreateOrderSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'product_id' => 'product123',
                'quantity' => 2,
            ])
        );

        $order = $this->createMock(Order::class);
        $order->method('toArray')->willReturn([
            'id' => 'order123',
            'productId' => 'product123',
            'status' => 'pending',
        ]);

        $this->ecommerceService
            ->expects($this->once())
            ->method('createOrder')
            ->with([
                'user_id' => 'seller123',
                'product_id' => 'product123',
                'quantity' => 2,
            ])
            ->willReturn($order);

        $result = $this->controller->createOrder($request);

        $this->assertEquals(201, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('order123', $data['id']);
    }

    public function testCreateOrderInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->createOrder($request);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateOrderMissingProductId(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['quantity' => 2])
        );

        $result = $this->controller->createOrder($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Product ID is required', $data['error']);
    }

    public function testCreateOrderException(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['product_id' => 'invalid'])
        );

        $this->ecommerceService
            ->expects($this->once())
            ->method('createOrder')
            ->willThrowException(new \InvalidArgumentException('Product not found'));

        $result = $this->controller->createOrder($request);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testGetMyOrders(): void
    {
        $request = new Request(['limit' => '10']);

        $orders = [
            ['id' => 'order1', 'status' => 'delivered'],
            ['id' => 'order2', 'status' => 'pending'],
        ];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getUserOrders')
            ->with('seller123', 10)
            ->willReturn($orders);

        $result = $this->controller->getMyOrders($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetMyOrdersDefaultLimit(): void
    {
        $request = new Request();
        $orders = [];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getUserOrders')
            ->with('seller123', 20)
            ->willReturn($orders);

        $result = $this->controller->getMyOrders($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetMyProducts(): void
    {
        $request = new Request(['limit' => '10']);

        $products = [
            ['id' => 'product1', 'name' => 'Product A'],
            ['id' => 'product2', 'name' => 'Product B'],
        ];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getSellerProducts')
            ->with('seller123', 10)
            ->willReturn($products);

        $result = $this->controller->getMyProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetMyProductsDefaultLimit(): void
    {
        $request = new Request();
        $products = [];

        $this->ecommerceService
            ->expects($this->once())
            ->method('getSellerProducts')
            ->with('seller123', 20)
            ->willReturn($products);

        $result = $this->controller->getMyProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSearchProducts(): void
    {
        $request = new Request(['q' => 'laptop', 'limit' => '5']);

        $products = [
            ['id' => 'product1', 'name' => 'Laptop A'],
            ['id' => 'product2', 'name' => 'Laptop B'],
        ];

        $this->ecommerceService
            ->expects($this->once())
            ->method('searchProducts')
            ->with('laptop', 5)
            ->willReturn($products);

        $result = $this->controller->searchProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testSearchProductsEmptyQuery(): void
    {
        $request = new Request(['q' => '']);

        $result = $this->controller->searchProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals([], $data);
    }

    public function testSearchProductsDefaultLimit(): void
    {
        $request = new Request(['q' => 'phone']);

        $products = [];

        $this->ecommerceService
            ->expects($this->once())
            ->method('searchProducts')
            ->with('phone', 20)
            ->willReturn($products);

        $result = $this->controller->searchProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSearchProductsWithoutQuery(): void
    {
        $request = new Request();

        $result = $this->controller->searchProducts($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals([], $data);
    }
}
