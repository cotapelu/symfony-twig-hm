<?php

declare(strict_types=1);

namespace Tests\Unit\GroceryDelivery\Controller;

use App\GroceryDelivery\Controller\GroceryDeliveryController;
use App\GroceryDelivery\Entity\DeliveryPerson;
use App\GroceryDelivery\Entity\Order as GroceryOrder;
use App\GroceryDelivery\Entity\Payment;
use App\GroceryDelivery\Entity\Store;
use App\GroceryDelivery\Service\DeliveryService;
use App\GroceryDelivery\Service\GroceryDeliveryService;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GroceryDeliveryControllerTest extends TestCase
{
    private GroceryDeliveryService $gdServiceMock;
    private DeliveryService $dServiceMock;
    private GroceryDeliveryController $controller;
    private TokenStorageInterface $tokenStorageMock;

    protected function setUp(): void
    {
        $this->gdServiceMock = $this->createMock(GroceryDeliveryService::class);
        $this->dServiceMock = $this->createMock(DeliveryService::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->controller = new GroceryDeliveryController($this->gdServiceMock, $this->dServiceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(?User $user = null): void
    {
        $this->controller->setTestUser($user);
        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $this->tokenStorageMock->method('getToken')->willReturn($token);
        } else {
            $this->tokenStorageMock->method('getToken')->willReturn(null);
        }
    }

    private function createUser(int $id, string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn((string)$id);
        $user->method('getUserIdentifier')->willReturn($email);
        return $user;
    }

    private function createStoreMock(array $data): Store
    {
        $mock = $this->createMock(Store::class);
        $mock->method('getId')->willReturn($data['id'] ?? '');
        $mock->method('getName')->willReturn($data['name'] ?? '');
        $mock->method('getAddress')->willReturn($data['address'] ?? '');
        $mock->method('getRating')->willReturn($data['rating'] ?? 0.0);
        $mock->method('getDeliveryFee')->willReturn($data['deliveryFee'] ?? 0);
        $mock->method('getDeliveryTime')->willReturn($data['deliveryTime'] ?? 0);
        return $mock;
    }

    private function createOrderMock(array $data): GroceryOrder
    {
        $mock = $this->createMock(GroceryOrder::class);
        $mock->method('getId')->willReturn($data['id'] ?? 0);
        $mock->method('getOrderNumber')->willReturn($data['orderNumber'] ?? '');
        $mock->method('getTotal')->willReturn($data['total'] ?? 0);
        $mock->method('getCustomerId')->willReturn($data['customerId'] ?? '');
        $mock->method('getStatus')->willReturn($data['status'] ?? '');
        return $mock;
    }

    private function createPaymentMock(array $data): Payment
    {
        $mock = $this->createMock(Payment::class);
        $mock->method('getTransactionId')->willReturn($data['transactionId'] ?? '');
        $mock->method('getAmount')->willReturn($data['amount'] ?? 0);
        return $mock;
    }

    private function createDeliveryPersonMock(array $data): DeliveryPerson
    {
        $mock = $this->createMock(DeliveryPerson::class);
        $mock->method('getId')->willReturn($data['id'] ?? '');
        $mock->method('getName')->willReturn($data['name'] ?? '');
        $mock->method('getRating')->willReturn($data['rating'] ?? 0.0);
        return $mock;
    }

    // ==================== getStores ====================

    public function testGetStores_NoParams_ReturnsEmpty(): void
    {
        $request = Request::create('/api/grocery-delivery/stores', 'GET');
        $response = $this->controller->getStores($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['stores']);
    }

    public function testGetStores_WithLatLng_ReturnsStores(): void
    {
        $store = $this->createStoreMock([
            'id' => 's1', 'name' => 'Store 1', 'address' => 'Addr1', 'rating' => 4.5, 'deliveryFee' => 1000, 'deliveryTime' => 30,
        ]);
        $this->gdServiceMock->method('getNearbyStores')->with(10.0, 106.0)->willReturn([$store]);

        $request = Request::create('/api/grocery-delivery/stores', 'GET', ['lat' => '10', 'lng' => '106']);
        $response = $this->controller->getStores($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['stores']);
        $this->assertSame('s1', $data['stores'][0]['id']);
    }

    public function testGetStores_InvalidLatitude_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/stores', 'GET', ['lat' => '100', 'lng' => '106']);
        $response = $this->controller->getStores($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testGetStores_InvalidLongitude_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/stores', 'GET', ['lat' => '10', 'lng' => '-200']);
        $response = $this->controller->getStores($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== createOrder ====================

    public function testCreateOrder_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $request = Request::create('/api/grocery-delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['items' => []]));
        $response = $this->controller->createOrder($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateOrder_InvalidJson_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = Request::create('/api/grocery-delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid');
        $response = $this->controller->createOrder($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreateOrder_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 123, 'orderNumber' => 'ORD-001', 'total' => 50000]);
        $this->gdServiceMock->method('createOrder')->with($this->arrayHasKey('items'))->willReturn($order);

        $request = Request::create('/api/grocery-delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['items' => [['productId' => 'p1', 'quantity' => 2]]]));
        $response = $this->controller->createOrder($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(123, $data['order']['id']);
    }

    public function testCreateOrder_ServiceException_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->gdServiceMock->method('createOrder')->willThrowException(new \Exception('Validation error'));

        $request = Request::create('/api/grocery-delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['items' => []]));
        $response = $this->controller->createOrder($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== confirm ====================

    public function testConfirm_InvalidId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = new Request();
        $response = $this->controller->confirm(0, $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testConfirm_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $request = new Request();
        $response = $this->controller->confirm(1, $request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testConfirm_OrderNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn(null);
        $request = new Request();
        $response = $this->controller->confirm(1, $request);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testConfirm_AccessDenied_Returns403(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '999']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $request = new Request();
        $response = $this->controller->confirm(1, $request);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testConfirm_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('confirmOrder')->with(1)->willReturn(false);
        $request = new Request();
        $response = $this->controller->confirm(1, $request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testConfirm_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('confirmOrder')->with(1)->willReturn($order);
        $request = new Request();
        $response = $this->controller->confirm(1, $request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ==================== ready ====================

    public function testReady_InvalidId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $response = $this->controller->ready(0, new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReady_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $response = $this->controller->ready(1, new Request());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testReady_OrderNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn(null);
        $response = $this->controller->ready(1, new Request());
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReady_AccessDenied_Returns403(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '999']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $response = $this->controller->ready(1, new Request());
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testReady_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('readyOrder')->with(1)->willReturn(false);
        $response = $this->controller->ready(1, new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReady_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('readyOrder')->with(1)->willReturn($order);
        $response = $this->controller->ready(1, new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['success']);
    }

    // ==================== completeDelivery ====================

    public function testCompleteDelivery_InvalidId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $response = $this->controller->completeDelivery(0, new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCompleteDelivery_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $response = $this->controller->completeDelivery(1, new Request());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCompleteDelivery_OrderNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn(null);
        $response = $this->controller->completeDelivery(1, new Request());
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCompleteDelivery_AccessDenied_Returns403(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '999']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $response = $this->controller->completeDelivery(1, new Request());
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testCompleteDelivery_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('completeDelivery')->with(1)->willReturn(false);
        $response = $this->controller->completeDelivery(1, new Request());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCompleteDelivery_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('completeDelivery')->with(1)->willReturn($order);
        $response = $this->controller->completeDelivery(1, new Request());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['success']);
    }

    // ==================== pay ====================

    public function testPay_InvalidId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = Request::create('/api/grocery-delivery/orders/0/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 0);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testPay_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testPay_OrderNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn(null);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testPay_AccessDenied_Returns403(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '999']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPay_InvalidJson_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid');
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testPay_MissingData_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testPay_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('processPayment')->with(1, $this->arrayHasKey('method'))->willReturn(false);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testPay_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $order = $this->createOrderMock(['id' => 1, 'customerId' => '1']);
        $payment = $this->createPaymentMock(['transactionId' => 'txn-123', 'amount' => 50000]);
        $this->gdServiceMock->method('getOrder')->with(1)->willReturn($order);
        $this->gdServiceMock->method('processPayment')->with(1, $this->arrayHasKey('method'))->willReturn($payment);
        $request = Request::create('/api/grocery-delivery/orders/1/pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['method' => 'card']));
        $response = $this->controller->pay($request, 1);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('txn-123', $data['payment']['transactionId']);
    }

    // ==================== customerOrders ====================

    public function testCustomerOrders_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $request = Request::create('/api/grocery-delivery/customer/orders', 'GET');
        $response = $this->controller->customerOrders($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCustomerOrders_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $orders = [
            $this->createOrderMock(['id' => 'o1', 'orderNumber' => 'ORD-001', 'status' => 'pending', 'total' => 20000]),
        ];
        $this->gdServiceMock->method('getCustomerOrders')->with('1')->willReturn($orders);
        $request = Request::create('/api/grocery-delivery/customer/orders', 'GET');
        $response = $this->controller->customerOrders($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['orders']);
    }

    // ==================== deliveryOnline ====================

    public function testDeliveryOnline_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);
        $request = Request::create('/api/grocery-delivery/delivery/online', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->deliveryOnline($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testDeliveryOnline_MissingDeliveryPersonId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = Request::create('/api/grocery-delivery/delivery/online', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        $response = $this->controller->deliveryOnline($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testDeliveryOnline_InvalidLatitude_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = Request::create('/api/grocery-delivery/delivery/online', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1', 'latitude' => 100]));
        $response = $this->controller->deliveryOnline($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testDeliveryOnline_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $dp = $this->createDeliveryPersonMock(['id' => 'dp1', 'name' => 'Driver 1']);
        $this->dServiceMock->method('setOnline')->with('dp1', 10.0, 106.0)->willReturn($dp);
        $request = Request::create('/api/grocery-delivery/delivery/online', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1', 'latitude' => 10.0, 'longitude' => 106.0]));
        $response = $this->controller->deliveryOnline($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['success']);
    }

    public function testDeliveryOnline_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->dServiceMock->method('setOnline')->willReturn(false);
        $request = Request::create('/api/grocery-delivery/delivery/online', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->deliveryOnline($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== deliveryOffline ====================

    public function testDeliveryOffline_MissingDeliveryPersonId_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $request = Request::create('/api/grocery-delivery/delivery/offline', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        $response = $this->controller->deliveryOffline($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testDeliveryOffline_Success(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $dp = $this->createDeliveryPersonMock(['id' => 'dp1']);
        $this->dServiceMock->method('setOffline')->with('dp1')->willReturn($dp);
        $request = Request::create('/api/grocery-delivery/delivery/offline', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->deliveryOffline($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['success']);
    }

    public function testDeliveryOffline_Failure_Returns400(): void
    {
        $user = $this->createUser(1, 'user@example.com');
        $this->setAuthenticatedUser($user);
        $this->dServiceMock->method('setOffline')->willReturn(false);
        $request = Request::create('/api/grocery-delivery/delivery/offline', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->deliveryOffline($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== nearbyDelivery ====================

    public function testNearbyDelivery_MissingLat_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/delivery/nearby', 'GET', ['lng' => '106']);
        $response = $this->controller->nearbyDelivery($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNearbyDelivery_MissingLng_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/delivery/nearby', 'GET', ['lat' => '10']);
        $response = $this->controller->nearbyDelivery($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNearbyDelivery_InvalidLatitude_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/delivery/nearby', 'GET', ['lat' => '100', 'lng' => '106']);
        $response = $this->controller->nearbyDelivery($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNearbyDelivery_InvalidLongitude_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/delivery/nearby', 'GET', ['lat' => '10', 'lng' => '-200']);
        $response = $this->controller->nearbyDelivery($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNearbyDelivery_Success(): void
    {
        $dp = $this->createDeliveryPersonMock(['id' => 'dp1', 'name' => 'Driver 1', 'rating' => 4.8]);
        $this->dServiceMock->method('findNearby')->with(10.0, 106.0)->willReturn([$dp]);
        $request = Request::create('/api/grocery-delivery/delivery/nearby', 'GET', ['lat' => '10', 'lng' => '106']);
        $response = $this->controller->nearbyDelivery($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['deliveryPersons']);
    }

    // ==================== assignOrder ====================

    public function testAssignOrder_InvalidId_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/orders/0/assign', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->assignOrder($request, 0);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAssignOrder_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/orders/1/assign', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid');
        $response = $this->controller->assignOrder($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAssignOrder_MissingDeliveryPersonId_Returns400(): void
    {
        $request = Request::create('/api/grocery-delivery/orders/1/assign', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        $response = $this->controller->assignOrder($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAssignOrder_Failure_Returns400(): void
    {
        $this->dServiceMock->method('assignOrder')->with(1, 'dp1')->willReturn(false);
        $request = Request::create('/api/grocery-delivery/orders/1/assign', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->assignOrder($request, 1);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAssignOrder_Success(): void
    {
        $order = $this->createOrderMock(['id' => 1]);
        $this->dServiceMock->method('assignOrder')->with(1, 'dp1')->willReturn($order);
        $request = Request::create('/api/grocery-delivery/orders/1/assign', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['deliveryPersonId' => 'dp1']));
        $response = $this->controller->assignOrder($request, 1);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['success']);
    }
}
