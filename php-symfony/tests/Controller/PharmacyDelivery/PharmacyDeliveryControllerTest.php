<?php

declare(strict_types=1);

namespace App\Tests\Controller\PharmacyDelivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Controller\PharmacyDeliveryController;
use App\PharmacyDelivery\Service\PharmacyDeliveryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class PharmacyDeliveryControllerTest extends TestCase
{
    private PharmacyDeliveryController $controller;
    private PharmacyDeliveryService|MockObject $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(PharmacyDeliveryService::class);
        $this->controller = new PharmacyDeliveryController($this->service);
        // Set authenticated user for tests
        $user = $this->createMock(\App\User\Entity\User::class);
        $user->method('getId')->willReturn('user123');
        $user->method('getUserIdentifier')->willReturn('user123');
        $this->controller->setTestUser($user);
    }

    public function testCreateOrderSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);
        $order->method('getOrderNumber')->willReturn('PHM-2024-001');

        $this->service
            ->expects($this->once())
            ->method('createOrder')
            ->with(['pharmacyId' => 'pharm1', 'items' => [['medicineId' => 1, 'quantity' => 2]]])
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'pharmacyId' => 'pharm1',
                'items' => [['medicineId' => 1, 'quantity' => 2]],
            ])
        );

        $result = $this->controller->createOrder($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(123, $data['orderId']);
        $this->assertEquals('PHM-2024-001', $data['orderNumber']);
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
        $this->service
            ->expects($this->once())
            ->method('createOrder')
            ->willThrowException(new \Exception('Pharmacy not found'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pharmacyId' => 'invalid'])
        );

        $result = $this->controller->createOrder($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Pharmacy not found', $data['error']);
    }

    public function testConfirmOrderSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('confirmOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->confirmOrder(123);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(123, $data['order']);
    }

    public function testConfirmOrderNotFound(): void
    {
        $this->service
            ->expects($this->once())
            ->method('confirmOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->confirmOrder(999);

        $this->assertEquals(404, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Order not found', $data['error']);
    }

    public function testPrepareOrderSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('prepareOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->prepareOrder(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testPrepareOrderFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('prepareOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->prepareOrder(999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid order', $data['error']);
    }

    public function testReadyOrderSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('readyOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->readyOrder(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testReadyOrderFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('readyOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->readyOrder(999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid order', $data['error']);
    }

    public function testAssignDeliveryPersonSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('assignDeliveryPerson')
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

        $result = $this->controller->assignDeliveryPerson($request, 123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testAssignDeliveryPersonInvalidJson(): void
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

        $result = $this->controller->assignDeliveryPerson($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testAssignDeliveryPersonFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('assignDeliveryPerson')
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

        $result = $this->controller->assignDeliveryPerson($request, 999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Assignment failed', $data['error']);
    }

    public function testProcessPaymentSuccess(): void
    {
        $payment = $this->createMock(\App\PharmacyDelivery\Entity\Payment::class);
        $payment->method('getId')->willReturn('pmt123');

        $this->service
            ->expects($this->once())
            ->method('processPayment')
            ->with(123, ['method' => 'card'])
            ->willReturn($payment);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['method' => 'card'])
        );

        $result = $this->controller->processPayment($request, 123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('pmt123', $data['paymentId']);
    }

    public function testProcessPaymentInvalidJson(): void
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

        $result = $this->controller->processPayment($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testProcessPaymentException(): void
    {
        $this->service
            ->expects($this->once())
            ->method('processPayment')
            ->with(123, ['method' => 'invalid'])
            ->willThrowException(new \Exception('Payment gateway error'));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['method' => 'invalid'])
        );

        $result = $this->controller->processPayment($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Payment gateway error', $data['error']);
    }

    public function testProcessPaymentFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('processPayment')
            ->with(123, ['method' => 'cash'])
            ->willReturn(null);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['method' => 'cash'])
        );

        $result = $this->controller->processPayment($request, 123);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Payment failed', $data['error']);
    }

    public function testCompleteDeliverySuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('completeDelivery')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->completeDelivery(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testCompleteDeliveryFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('completeDelivery')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->completeDelivery(999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid order', $data['error']);
    }

    public function testCancelOrderSuccess(): void
    {
        $order = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(123);

        $this->service
            ->expects($this->once())
            ->method('cancelOrder')
            ->with(123)
            ->willReturn($order);

        $result = $this->controller->cancelOrder(123);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testCancelOrderFailure(): void
    {
        $this->service
            ->expects($this->once())
            ->method('cancelOrder')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->cancelOrder(999);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Cancel failed', $data['error']);
    }

    public function testGetNearbyPharmacies(): void
    {
        $pharmacy1 = $this->createMock(\App\PharmacyDelivery\Entity\Pharmacy::class);
        $pharmacy1->method('getId')->willReturn('pharm1');
        $pharmacy1->method('getName')->willReturn('Pharmacy A');
        $pharmacy1->method('getAddress')->willReturn('123 Main St');
        $pharmacy1->method('getDeliveryFee')->willReturn(15000);
        $pharmacy1->method('getEstimatedDeliveryTime')->willReturn('30 min');
        $pharmacy1->method('getRating')->willReturn(4.5);
        $pharmacy1->method('isOpenNow')->willReturn(true);

        $pharmacy2 = $this->createMock(\App\PharmacyDelivery\Entity\Pharmacy::class);
        $pharmacy2->method('getId')->willReturn('pharm2');
        $pharmacy2->method('getName')->willReturn('Pharmacy B');
        $pharmacy2->method('getAddress')->willReturn('456 Oak Ave');
        $pharmacy2->method('getDeliveryFee')->willReturn(20000);
        $pharmacy2->method('getEstimatedDeliveryTime')->willReturn('45 min');
        $pharmacy2->method('getRating')->willReturn(4.8);
        $pharmacy2->method('isOpenNow')->willReturn(false);

        $pharmacies = [$pharmacy1, $pharmacy2];

        $this->service
            ->expects($this->once())
            ->method('getNearbyPharmacies')
            ->with(21.0285, 105.8542, 5)
            ->willReturn($pharmacies);

        $request = new Request(['lat' => '21.0285', 'lng' => '105.8542', 'radius' => '5']);

        $result = $this->controller->getNearbyPharmacies($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('pharmacies', $data);
        $this->assertCount(2, $data['pharmacies']);
        $this->assertEquals('Pharmacy A', $data['pharmacies'][0]['name']);
        $this->assertTrue($data['pharmacies'][0]['isOpen']);
        $this->assertFalse($data['pharmacies'][1]['isOpen']);
    }

    public function testGetNearbyPharmaciesDefaultRadius(): void
    {
        $pharmacies = [];

        $this->service
            ->expects($this->once())
            ->method('getNearbyPharmacies')
            ->with(21.0285, 105.8542, 5)
            ->willReturn($pharmacies);

        $request = new Request(['lat' => '21.0285', 'lng' => '105.8542']);

        $result = $this->controller->getNearbyPharmacies($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetPharmacyMedicines(): void
    {
        $medicine1 = $this->createMock(\App\PharmacyDelivery\Entity\Medicine::class);
        $medicine1->method('getId')->willReturn('med1');
        $medicine1->method('getName')->willReturn('Medicine A');
        $medicine1->method('getGenericName')->willReturn('Generic A');
        $medicine1->method('getPrice')->willReturn(50000);
        $medicine1->method('getCategory')->willReturn('Antibiotic');
        $medicine1->method('requiresPrescription')->willReturn(true);
        $medicine1->method('getQuantityInStock')->willReturn(10);

        $medicine2 = $this->createMock(\App\PharmacyDelivery\Entity\Medicine::class);
        $medicine2->method('getId')->willReturn('med2');
        $medicine2->method('getName')->willReturn('Medicine B');
        $medicine2->method('getGenericName')->willReturn('Generic B');
        $medicine2->method('getPrice')->willReturn(30000);
        $medicine2->method('getCategory')->willReturn('Painkiller');
        $medicine2->method('requiresPrescription')->willReturn(false);
        $medicine2->method('getQuantityInStock')->willReturn(0);

        $medicines = [$medicine1, $medicine2];

        $this->service
            ->expects($this->once())
            ->method('getPharmacyMedicines')
            ->with(1)
            ->willReturn($medicines);

        $result = $this->controller->getPharmacyMedicines(1);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('medicines', $data);
        $this->assertCount(2, $data['medicines']);
        $this->assertEquals('Medicine A', $data['medicines'][0]['name']);
        $this->assertTrue($data['medicines'][0]['requiresPrescription']);
        $this->assertTrue($data['medicines'][0]['inStock']);
        $this->assertFalse($data['medicines'][1]['inStock']);
    }

    public function testSearchMedicines(): void
    {
        $medicine = $this->createMock(\App\PharmacyDelivery\Entity\Medicine::class);
        $medicine->method('getId')->willReturn('med1');
        $medicine->method('getName')->willReturn('Paracetamol');
        $medicine->method('getGenericName')->willReturn('Acetaminophen');
        $medicine->method('getPrice')->willReturn(10000);
        $medicine->method('getCategory')->willReturn('Painkiller');
        $medicine->method('requiresPrescription')->willReturn(false);

        $medicines = [$medicine];

        $this->service
            ->expects($this->once())
            ->method('searchMedicines')
            ->with('paracetamol', 1)
            ->willReturn($medicines);

        $request = new Request(['q' => 'paracetamol', 'pharmacyId' => '1']);

        $result = $this->controller->searchMedicines($request);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('medicines', $data);
        $this->assertCount(1, $data['medicines']);
        $this->assertEquals('Paracetamol', $data['medicines'][0]['name']);
    }

    public function testSearchMedicinesWithoutPharmacy(): void
    {
        $medicines = [];

        $this->service
            ->expects($this->once())
            ->method('searchMedicines')
            ->with('aspirin', null)
            ->willReturn($medicines);

        $request = new Request(['q' => 'aspirin']);

        $result = $this->controller->searchMedicines($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetCustomerOrders(): void
    {
        $order1 = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order1->method('getId')->willReturn(1);
        $order1->method('getOrderNumber')->willReturn('PHM-001');
        $order1->method('getStatus')->willReturn('delivered');
        $order1->method('getTotal')->willReturn(100000);
        $order1->method('getCreatedAt')->willReturn(new \DateTime('2024-01-01 10:00:00'));

        $order2 = $this->createMock(\App\PharmacyDelivery\Entity\Order::class);
        $order2->method('getId')->willReturn(2);
        $order2->method('getOrderNumber')->willReturn('PHM-002');
        $order2->method('getStatus')->willReturn('pending');
        $order2->method('getTotal')->willReturn(150000);
        $order2->method('getCreatedAt')->willReturn(new \DateTime('2024-01-02 10:00:00'));

        $orders = [$order1, $order2];

        $this->service
            ->expects($this->once())
            ->method('getCustomerOrders')
            ->with('user123')
            ->willReturn($orders);

        $result = $this->controller->getCustomerOrders('user123');

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('orders', $data);
        $this->assertCount(2, $data['orders']);
        $this->assertEquals('PHM-001', $data['orders'][0]['orderNumber']);
        $this->assertEquals('delivered', $data['orders'][0]['status']);
        $this->assertEquals(100000, $data['orders'][0]['total']);
    }
}
