<?php

declare(strict_types=1);

namespace App\Tests\Controller\GroceryDelivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Controller\GroceryDeliveryController;
use App\GroceryDelivery\Service\DeliveryService;
use App\GroceryDelivery\Service\GroceryDeliveryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class GroceryDeliveryControllerTest extends TestCase
{
    private GroceryDeliveryController $controller;
    private GroceryDeliveryService|MockObject $groceryDeliveryService;
    private DeliveryService|MockObject $deliveryService;

    protected function setUp(): void
    {
        $this->groceryDeliveryService = $this->createMock(GroceryDeliveryService::class);
        $this->deliveryService = $this->createMock(DeliveryService::class);

        $this->controller = new GroceryDeliveryController(
            $this->groceryDeliveryService,
            $this->deliveryService
        );
        // Set authenticated user for tests
        $user = $this->createMock(\App\User\Entity\User::class);
        $user->method('getId')->willReturn('user123');
        $user->method('getUserIdentifier')->willReturn('user123');
        $this->controller->setTestUser($user);
    }

    public function testGetStoresWithCoordinates(): void
    {
        $request = new Request(['lat' => '21.0285', 'lng' => '105.8542']);

        $store1 = $this->createMock(\App\GroceryDelivery\Entity\Store::class);
        $store1->method('getId')->willReturn('store1');
        $store1->method('getName')->willReturn('Grocery Store 1');
        $store1->method('getAddress')->willReturn('123 Main St');
        $store1->method('getRating')->willReturn(4.5);
        $store1->method('getDeliveryFee')->willReturn(15000);
        $store1->method('getDeliveryTime')->willReturn('30-45 min');

        $store2 = $this->createMock(\App\GroceryDelivery\Entity\Store::class);
        $store2->method('getId')->willReturn('store2');
        $store2->method('getName')->willReturn('Grocery Store 2');
        $store2->method('getAddress')->willReturn('456 Oak Ave');
        $store2->method('getRating')->willReturn(4.2);
        $store2->method('getDeliveryFee')->willReturn(20000);
        $store2->method('getDeliveryTime')->willReturn('25-35 min');

        $stores = [$store1, $store2];

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getNearbyStores')
            ->with(21.0285, 105.8542)
            ->willReturn($stores);

        $result = $this->controller->getStores($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('stores', $data);
        $this->assertCount(2, $data['stores']);
        $this->assertEquals('Grocery Store 1', $data['stores'][0]['name']);
        $this->assertEquals('Grocery Store 2', $data['stores'][1]['name']);
    }

    public function testGetStoresWithoutCoordinates(): void
    {
        $request = new Request();

        $this->groceryDeliveryService
            ->expects($this->never())
            ->method('getNearbyStores');

        $result = $this->controller->getStores($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['stores']);
    }

    public function testCreateOrderSuccess(): void
    {
        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);
        $order->method('getOrderNumber')->willReturn('GRS-2024-001');
        $order->method('getTotal')->willReturn(150000);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('createOrder')
            ->with(['storeId' => 'store1', 'items' => [['productId' => 1, 'quantity' => 2]]])
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'storeId' => 'store1',
                'items' => [['productId' => 1, 'quantity' => 2]]
            ])
        );

        $result = $this->controller->createOrder($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('order', $data);
        $this->assertEquals(123, $data['order']['id']);
        $this->assertEquals('GRS-2024-001', $data['order']['orderNumber']);
        $this->assertEquals(150000, $data['order']['total']);
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

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreateOrderException(): void
    {
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('createOrder')
            ->willThrowException(new \Exception('Store not found'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['storeId' => 'invalid'])
        );

        $result = $this->controller->createOrder($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Store not found', $data['error']);
    }

    public function testConfirmOrderSuccess(): void
    {
        // Mock for getOrder call
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($getOrder);

        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('confirmOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->confirm(123, new Request());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testConfirmOrderFailure(): void
    {
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(999)
            ->willReturn($getOrder);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('confirmOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->confirm(999, new Request());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed', $data['error']);
    }

    public function testReadyOrderSuccess(): void
    {
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($getOrder);

        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('readyOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->ready(123, new Request());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testReadyOrderFailure(): void
    {
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(999)
            ->willReturn($getOrder);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('readyOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->ready(999, new Request());

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed', $data['error']);
    }

    public function testCompleteDeliverySuccess(): void
    {
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($getOrder);

        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('completeDelivery')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->completeDelivery(123, new Request());

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testCompleteDeliveryFailure(): void
    {
        $getOrder = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $getOrder->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(999)
            ->willReturn($getOrder);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('completeDelivery')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->completeDelivery(999, new Request());

        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed', $data['error']);
    }

    public function testPayOrderSuccess(): void
    {
        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($order);

        $payment = $this->createMock(\App\GroceryDelivery\Entity\Payment::class);
        $payment->method('getTransactionId')->willReturn('txn123');
        $payment->method('getAmount')->willReturn(150000);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('processPayment')
            ->with(123, ['method' => 'card', 'cardNumber' => '****1234'])
            ->willReturn($payment);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['method' => 'card', 'cardNumber' => '****1234'])
        );

        $result = $this->controller->pay($request, 123);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('txn123', $data['payment']['transactionId']);
        $this->assertEquals(150000, $data['payment']['amount']);
    }

    public function testPayOrderInvalidJson(): void
    {
        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->pay($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testPayOrderFailure(): void
    {
        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getCustomerId')->willReturn('user123');
        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getOrder')
            ->with(123)
            ->willReturn($order);

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('processPayment')
            ->with(123, ['method' => 'invalid'])
            ->willReturn(null);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['method' => 'invalid'])
        );

        $result = $this->controller->pay($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed', $data['error']);
    }

    public function testCustomerOrders(): void
    {
        $order1 = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order1->method('getId')->willReturn(1);
        $order1->method('getOrderNumber')->willReturn('GRS-001');
        $order1->method('getStatus')->willReturn('delivered');
        $order1->method('getTotal')->willReturn(100000);

        $order2 = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order2->method('getId')->willReturn(2);
        $order2->method('getOrderNumber')->willReturn('GRS-002');
        $order2->method('getStatus')->willReturn('pending');
        $order2->method('getTotal')->willReturn(150000);

        $orders = [$order1, $order2];

        $this->groceryDeliveryService
            ->expects($this->once())
            ->method('getCustomerOrders')
            ->with('user123')
            ->willReturn($orders);

        $result = $this->controller->customerOrders(new Request());

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['orders']);
        $this->assertEquals('GRS-001', $data['orders'][0]['orderNumber']);
        $this->assertEquals('delivered', $data['orders'][0]['status']);
    }

    public function testDeliveryOnline(): void
    {
        $deliveryPerson = $this->createMock(\App\GroceryDelivery\Entity\DeliveryPerson::class);
        $deliveryPerson->method('getId')->willReturn('dp1');

        $this->deliveryService
            ->expects($this->once())
            ->method('setOnline')
            ->with('dp1', 21.0285, 105.8542)
            ->willReturn($deliveryPerson);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'deliveryPersonId' => 'dp1',
                'latitude' => 21.0285,
                'longitude' => 105.8542,
            ])
        );

        $result = $this->controller->deliveryOnline($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testDeliveryOnlineInvalidJson(): void
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

        $result = $this->controller->deliveryOnline($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testDeliveryOffline(): void
    {
        $deliveryPerson = $this->createMock(\App\GroceryDelivery\Entity\DeliveryPerson::class);
        $deliveryPerson->method('getId')->willReturn('dp1');

        $this->deliveryService
            ->expects($this->once())
            ->method('setOffline')
            ->with('dp1')
            ->willReturn($deliveryPerson);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['deliveryPersonId' => 'dp1'])
        );

        $result = $this->controller->deliveryOffline($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testDeliveryOfflineInvalidJson(): void
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

        $result = $this->controller->deliveryOffline($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testNearbyDelivery(): void
    {
        $dp1 = $this->createMock(\App\GroceryDelivery\Entity\DeliveryPerson::class);
        $dp1->method('getId')->willReturn('dp1');
        $dp1->method('getName')->willReturn('Driver 1');
        $dp1->method('getRating')->willReturn(4.5);

        $dp2 = $this->createMock(\App\GroceryDelivery\Entity\DeliveryPerson::class);
        $dp2->method('getId')->willReturn('dp2');
        $dp2->method('getName')->willReturn('Driver 2');
        $dp2->method('getRating')->willReturn(4.8);

        $deliveryPersons = [$dp1, $dp2];

        $this->deliveryService
            ->expects($this->once())
            ->method('findNearby')
            ->with(21.0285, 105.8542)
            ->willReturn($deliveryPersons);

        $request = new Request(['lat' => '21.0285', 'lng' => '105.8542']);

        $result = $this->controller->nearbyDelivery($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['deliveryPersons']);
        $this->assertEquals('Driver 1', $data['deliveryPersons'][0]['name']);
        $this->assertEquals(4.5, $data['deliveryPersons'][0]['rating']);
    }

    public function testAssignOrder(): void
    {
        $order = $this->createMock(\App\GroceryDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->deliveryService
            ->expects($this->once())
            ->method('assignOrder')
            ->with(123, 'dp1')
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['deliveryPersonId' => 'dp1'])
        );

        $result = $this->controller->assignOrder($request, 123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testAssignOrderFailure(): void
    {
        $this->deliveryService
            ->expects($this->once())
            ->method('assignOrder')
            ->with(999, 'dp1')
            ->willReturn(null);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['deliveryPersonId' => 'dp1'])
        );

        $result = $this->controller->assignOrder($request, 999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Failed', $data['error']);
    }

    public function testAssignOrderInvalidJson(): void
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

        $result = $this->controller->assignOrder($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }
}
