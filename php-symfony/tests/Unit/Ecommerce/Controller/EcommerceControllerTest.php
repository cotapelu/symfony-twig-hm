<?php declare(strict_types=1);

namespace Tests\Unit\Ecommerce\Controller;

use App\Ecommerce\Controller\EcommerceController;
use App\Ecommerce\Service\EcommerceService;
use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Entity\EcommerceOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\User\Entity\User;

class EcommerceControllerTest extends TestCase
{
    private EcommerceService&MockObject $serviceMock;
    private EcommerceController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(EcommerceService::class);
        $this->controller = new EcommerceController($this->serviceMock);
        $container = new ContainerBuilder();
        $this->controller->setContainer($container);
    }

    private function createUser(string $id): User
    {
        return new User($id, "user{$id}@example.com");
    }

    private function createProduct(array $overrides = []): EcommerceProduct
    {
        $product = new EcommerceProduct();
        $product->setId($overrides['id'] ?? 'prod-'.uniqid());
        $product->setSellerId($overrides['sellerId'] ?? 'seller-1');
        $product->setName($overrides['name'] ?? 'Test Product');
        $product->setPrice($overrides['price'] ?? 10.0);
        $product->setStock($overrides['stock'] ?? 100);
        $product->setCategory($overrides['category'] ?? 'electronics');
        $product->setDescription($overrides['description'] ?? 'Desc');
        $product->setCurrency($overrides['currency'] ?? 'USD');
        return $product;
    }

    private function createOrder(array $overrides = []): EcommerceOrder
    {
        $order = new EcommerceOrder();
        $order->setId($overrides['id'] ?? 'order-'.uniqid());
        $order->setUserId($overrides['userId'] ?? 'user-1');
        $order->setProductId($overrides['productId'] ?? 'prod-1');
        $order->setQuantity($overrides['quantity'] ?? 1);
        $order->setTotalPrice($overrides['totalPrice'] ?? 10.0);
        $order->setStatus($overrides['status'] ?? 'pending');
        return $order;
    }

    private function assertJsonResponse(JsonResponse $response, int $expectedStatus, ?string $expectedPartial = null): void
    {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        if ($expectedPartial !== null) {
            $this->assertStringContainsString($expectedPartial, $response->getContent());
        }
    }

    private function assertJsonResponseData(JsonResponse $response, array $expectedData): void
    {
        $data = json_decode($response->getContent(), true);
        $this->assertSame($expectedData, $data);
    }

    private function authenticate(string $userId = 'user-1'): User
    {
        $user = $this->createUser($userId);
        $this->controller->setAuthenticatedUser($user);
        return $user;
    }

    // ==================== PUBLIC ENDPOINTS ====================

    public function testGetProducts_Success(): void
    {
        $productArray = [
            ['id' => 'p1', 'name' => 'Product 1', 'price' => 10],
            ['id' => 'p2', 'name' => 'Product 2', 'price' => 20],
        ];
        $this->serviceMock->method('countActiveProducts')->willReturn(2);
        $this->serviceMock->method('getProducts')->with(20)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products', 'GET');
        $response = $this->controller->getProducts($request);
        $this->assertJsonResponseData($response, $productArray);
    }

    public function testGetProducts_WithLimit(): void
    {
        $productArray = [['id' => 'p1', 'name' => 'P1', 'price' => 5]];
        $this->serviceMock->method('countActiveProducts')->willReturn(1);
        $this->serviceMock->method('getProducts')->with(50)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products', 'GET', ['limit' => '50']);
        $response = $this->controller->getProducts($request);
        $this->assertJsonResponseData($response, $productArray);
    }

    public function testGetProducts_InvalidLimit_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products', 'GET', ['limit' => '0']);
        $response = $this->controller->getProducts($request);
        $this->assertJsonResponse($response, 400);
    }

    public function testGetProductsByCategory_Success(): void
    {
        $productArray = [['id' => 'p1', 'category' => 'books']];
        $this->serviceMock->method('getProductsByCategory')->with('books', 20)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products/category/books', 'GET');
        $response = $this->controller->getProductsByCategory('books', $request);
        $this->assertJsonResponseData($response, $productArray);
    }

    public function testGetProductsByCategory_EmptyCategory_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/category/', 'GET');
        $response = $this->controller->getProductsByCategory('', $request);
        $this->assertJsonResponse($response, 400);
    }

    public function testSearchProducts_EmptyQuery_ReturnsEmptyArray(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/search', 'GET', ['q' => '']);
        $response = $this->controller->searchProducts($request);
        $this->assertJsonResponseData($response, []);
    }

    public function testSearchProducts_ShortQuery_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/search', 'GET', ['q' => 'a']);
        $response = $this->controller->searchProducts($request);
        $this->assertJsonResponse($response, 400);
    }

    public function testSearchProducts_Success(): void
    {
        $productArray = [['id' => 'p1', 'name' => 'Phone']];
        $this->serviceMock->method('searchProducts')->with('phone', 20)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products/search', 'GET', ['q' => 'phone']);
        $response = $this->controller->searchProducts($request);
        $this->assertJsonResponseData($response, array_values($productArray));
    }

    public function testGetFeaturedProducts_Success(): void
    {
        $productArray = [['id' => 'p1', 'name' => 'Featured']];
        $this->serviceMock->method('getFeaturedProducts')->with(10)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products/featured', 'GET');
        $response = $this->controller->getFeaturedProducts($request);
        $expected = ['success' => true, 'data' => array_values($productArray)];
        $this->assertJsonResponseData($response, $expected);
    }

    // ==================== AUTHENTICATED ENDPOINTS ====================

    public function testCreateProduct_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test', 'price' => 10]));
        $response = $this->controller->createProduct($request);
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testCreateProduct_InvalidJson_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createProduct($request);
        $this->assertJsonResponse($response, 400);
    }

    public function testCreateProduct_MissingRequired_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test']));
        $response = $this->controller->createProduct($request);
        $this->assertJsonResponse($response, 400, 'Name and price are required');
    }

    public function testCreateProduct_InvalidPrice_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test', 'price' => '-5']));
        $response = $this->controller->createProduct($request);
        $this->assertJsonResponse($response, 400, 'Price range invalid');
    }

    public function testCreateProduct_NameTooLong_Returns400(): void
    {
        $this->authenticate();
        $longName = str_repeat('a', 201);
        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => $longName, 'price' => 10]));
        $response = $this->controller->createProduct($request);
        $this->assertJsonResponse($response, 400);
        $this->assertStringContainsString('Name', $response->getContent());
        $this->assertStringContainsString('200', $response->getContent()); // Contains "200 chars"
    }

    public function testCreateProduct_Success(): void
    {
        $this->authenticate('seller-1');
        $product = $this->createProduct(['sellerId' => 'seller-1']);
        $this->serviceMock->expects($this->once())
            ->method('createProduct')
            ->with($this->callback(fn($data) => $data['seller_id'] === 'seller-1' && $data['name'] === 'New Product' && $data['price'] == 20.0))
            ->willReturn($product);

        $request = Request::create('/api/v1/ecommerce/products', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'New Product', 'price' => '20']));
        $response = $this->controller->createProduct($request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('seller-1', $data['sellerId']);
    }

    public function testGetProduct_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getProduct('prod-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testGetProduct_EmptyId_Returns400(): void
    {
        $this->authenticate();
        $response = $this->controller->getProduct('');
        $this->assertJsonResponse($response, 400);
    }

    public function testGetProduct_NotFound_Returns404(): void
    {
        $this->authenticate();
        $this->serviceMock->method('getProduct')->with('nonexistent')->willReturn(null);

        $response = $this->controller->getProduct('nonexistent');
        $this->assertJsonResponse($response, 404);
    }

    public function testGetProduct_Success(): void
    {
        $this->authenticate();
        $product = $this->createProduct(['id' => 'prod-123']);
        $this->serviceMock->method('getProduct')->with('prod-123')->willReturn($product);

        $response = $this->controller->getProduct('prod-123');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('prod-123', $data['id']);
    }

    public function testUpdateProduct_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/prod-1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['price' => 10]));
        $response = $this->controller->updateProduct($request, 'prod-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testUpdateProduct_EmptyId_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products/', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['price' => 10]));
        $response = $this->controller->updateProduct($request, '');
        $this->assertJsonResponse($response, 400);
    }

    public function testUpdateProduct_Success(): void
    {
        $this->authenticate('owner-1');
        $product = $this->createProduct(['sellerId' => 'owner-1']);
        $this->serviceMock->expects($this->once())
            ->method('updateProduct')
            ->with('prod-123', $this->callback(fn($data) => $data['seller_id'] === 'owner-1' && array_key_exists('price', $data)))
            ->willReturn($product);

        $request = Request::create('/api/v1/ecommerce/products/prod-123', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['price' => 15.0]));
        $response = $this->controller->updateProduct($request, 'prod-123');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Product updated successfully', $response->getContent());
    }

    public function testDeleteProduct_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->deleteProduct('prod-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testDeleteProduct_EmptyId_Returns400(): void
    {
        $this->authenticate();
        $response = $this->controller->deleteProduct('');
        $this->assertJsonResponse($response, 400);
    }

    public function testDeleteProduct_NotFound_Returns404(): void
    {
        $this->authenticate();
        $this->serviceMock->method('getProduct')->with('nonexistent')->willReturn(null);

        $response = $this->controller->deleteProduct('nonexistent');
        $this->assertJsonResponse($response, 404);
    }

    public function testDeleteProduct_NotOwner_Returns403(): void
    {
        $this->authenticate('owner-1');
        $product = $this->createProduct(['id' => 'prod-123', 'sellerId' => 'owner-2']);
        $this->serviceMock->method('getProduct')->with('prod-123')->willReturn($product);

        $response = $this->controller->deleteProduct('prod-123');
        $this->assertJsonResponse($response, 403);
    }

    public function testDeleteProduct_Success(): void
    {
        $this->authenticate('owner-1');
        $product = $this->createProduct(['id' => 'prod-123', 'sellerId' => 'owner-1']);
        $this->serviceMock->method('getProduct')->with('prod-123')->willReturn($product);
        $this->serviceMock->method('deleteProduct')->with('prod-123', 'owner-1')->willReturn(true);

        $response = $this->controller->deleteProduct('prod-123');
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testCreateOrder_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/v1/ecommerce/orders', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['productId' => 'prod-1']));
        $response = $this->controller->createOrder($request);
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testCreateOrder_MissingProductId_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/orders', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => 2]));
        $response = $this->controller->createOrder($request);
        $this->assertJsonResponse($response, 400, 'Product ID is required');
    }

    public function testCreateOrder_Success(): void
    {
        $this->authenticate('user-1');
        $order = $this->createOrder(['userId' => 'user-1', 'productId' => 'prod-1']);
        $this->serviceMock->expects($this->once())
            ->method('createOrder')
            ->with($this->callback(fn($data) => $data['user_id'] === 'user-1' && $data['product_id'] === 'prod-1' && $data['quantity'] == 2))
            ->willReturn($order);

        $request = Request::create('/api/v1/ecommerce/orders', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'Idempotency-Key' => 'key-123'], json_encode(['productId' => 'prod-1', 'quantity' => '2']));
        $response = $this->controller->createOrder($request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('user-1', $data['userId']);
    }

    public function testGetOrder_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getOrder('order-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testGetOrder_NotFound_Returns404(): void
    {
        $this->authenticate();
        $this->serviceMock->method('getOrder')->with('nonexistent', 'user-1')->willReturn(null);

        $response = $this->controller->getOrder('nonexistent');
        $this->assertJsonResponse($response, 404);
    }

    public function testGetOrder_Success(): void
    {
        $this->authenticate('user-1');
        $order = $this->createOrder(['userId' => 'user-1']);
        $this->serviceMock->method('getOrder')->with('order-123', 'user-1')->willReturn($order);

        $response = $this->controller->getOrder('order-123');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('user-1', $data['userId']);
    }

    public function testGetMyOrders_Unauthenticated_Returns401(): void
    {
        $request = Request::create('/api/v1/ecommerce/orders/user/me', 'GET');
        $response = $this->controller->getMyOrders($request);
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testGetMyOrders_Success(): void
    {
        $this->authenticate('user-1');
        $order = $this->createOrder(['userId' => 'user-1']);
        $this->serviceMock->method('getUserOrders')->with('user-1', 20)->willReturn([$order]);

        $request = Request::create('/api/v1/ecommerce/orders/user/me', 'GET');
        $response = $this->controller->getMyOrders($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfirmOrder_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->confirmOrder('order-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testConfirmOrder_Success(): void
    {
        $this->authenticate('user-1');
        $order = $this->createOrder(['userId' => 'user-1', 'status' => 'confirmed']);
        $this->serviceMock->method('confirmOrder')->with('order-123', 'user-1')->willReturn($order);

        $response = $this->controller->confirmOrder('order-123');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Order confirmed', $response->getContent());
    }

    public function testCancelOrder_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->cancelOrder('order-1');
        $this->assertJsonResponse($response, 401, '"error":"Authentication required"');
    }

    public function testCancelOrder_Success(): void
    {
        $this->authenticate('user-1');
        $order = $this->createOrder(['userId' => 'user-1', 'status' => 'cancelled']);
        $this->serviceMock->method('cancelOrder')->with('order-123', 'user-1')->willReturn($order);

        $response = $this->controller->cancelOrder('order-123');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Order cancelled', $response->getContent());
    }

    public function testGetMyProducts_Unauthenticated_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/seller/me', 'GET');
        $response = $this->controller->getMyProducts($request);
        $this->assertJsonResponse($response, 400, 'Authentication required');
    }

    public function testGetMyProducts_Success(): void
    {
        $this->authenticate('seller-1');
        $productArray = [['id' => 'p1', 'sellerId' => 'seller-1']];
        $this->serviceMock->method('getSellerProducts')->with('seller-1', 20)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products/seller/me', 'GET');
        $response = $this->controller->getMyProducts($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['success' => true, 'data' => $productArray], $data);
    }

    public function testGetSellerProducts_OwnProducts_Success(): void
    {
        $this->authenticate('seller-1');
        $productArray = [['id' => 'p1', 'sellerId' => 'seller-1']];
        $this->serviceMock->method('getSellerProducts')->with('seller-1', 20)->willReturn($productArray);

        $request = Request::create('/api/v1/ecommerce/products/seller/seller-1', 'GET');
        $response = $this->controller->getSellerProducts('seller-1', $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonResponseData($response, $productArray);
    }

    public function testGetSellerProducts_OtherSeller_Returns403(): void
    {
        $this->authenticate('seller-1');
        $request = Request::create('/api/v1/ecommerce/products/seller/seller-2', 'GET');
        $response = $this->controller->getSellerProducts('seller-2', $request);
        $this->assertJsonResponse($response, 403);
    }

    public function testGetSellerProducts_Unauthenticated_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/seller/seller-1', 'GET');
        $response = $this->controller->getSellerProducts('seller-1', $request);
        $this->assertJsonResponse($response, 400, 'Authentication required');
    }

    public function testUpdateStock_Unauthenticated_Returns400(): void
    {
        $request = Request::create('/api/v1/ecommerce/products/prod-1/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => 10]));
        $response = $this->controller->updateStock('prod-1', $request);
        $this->assertJsonResponse($response, 400, 'Authentication required');
    }

    public function testUpdateStock_ProductNotFound_Returns404(): void
    {
        $this->authenticate();
        $this->serviceMock->method('getProduct')->with('nonexistent')->willReturn(null);

        $request = Request::create('/api/v1/ecommerce/products/nonexistent/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => 10]));
        $response = $this->controller->updateStock('nonexistent', $request);
        $this->assertJsonResponse($response, 404);
    }

    public function testUpdateStock_NotOwner_Returns403(): void
    {
        $this->authenticate('seller-1');
        $product = $this->createProduct(['sellerId' => 'seller-2']);
        $this->serviceMock->method('getProduct')->with('prod-1')->willReturn($product);

        $request = Request::create('/api/v1/ecommerce/products/prod-1/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => 10]));
        $response = $this->controller->updateStock('prod-1', $request);
        $this->assertJsonResponse($response, 403);
    }

    public function testUpdateStock_InvalidQuantity_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products/prod-1/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => '-5']));
        $response = $this->controller->updateStock('prod-1', $request);
        $this->assertJsonResponse($response, 400, 'Quantity cannot be negative');
    }

    public function testUpdateStock_MissingQuantity_Returns400(): void
    {
        $this->authenticate();
        $request = Request::create('/api/v1/ecommerce/products/prod-1/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->updateStock('prod-1', $request);
        $this->assertJsonResponse($response, 400, 'Quantity required');
    }

    public function testUpdateStock_Success(): void
    {
        $this->authenticate('seller-1');
        $product = $this->createProduct(['id' => 'prod-1', 'sellerId' => 'seller-1', 'stock' => 50]);
        $this->serviceMock->method('getProduct')->with('prod-1')->willReturn($product);
        $this->serviceMock->method('updateStock')->with('prod-1', 30, 'seller-1')->willReturn($product);

        $request = Request::create('/api/v1/ecommerce/products/prod-1/stock', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['quantity' => '30']));
        $response = $this->controller->updateStock('prod-1', $request);
        $this->assertSame(200, $response->getStatusCode());
        // Body contains success true and data (product object serialized to {} without serializer); just check it's a JSON object
        $this->assertJson($response->getContent());
    }
}
