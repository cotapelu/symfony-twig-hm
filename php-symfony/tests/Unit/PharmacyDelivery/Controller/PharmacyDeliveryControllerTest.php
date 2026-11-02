<?php declare(strict_types=1);

namespace Tests\Unit\PharmacyDelivery\Controller;

use App\PharmacyDelivery\Controller\PharmacyDeliveryController;
use App\PharmacyDelivery\Service\PharmacyDeliveryService;
use App\PharmacyDelivery\Entity\Pharmacy;
use App\PharmacyDelivery\Entity\Medicine;
use App\PharmacyDelivery\Entity\Order;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use InvalidArgumentException;

class PharmacyDeliveryControllerTest extends TestCase
{
    private PharmacyDeliveryService&MockObject $serviceMock;
    private PharmacyDeliveryController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(PharmacyDeliveryService::class);
        $this->controller = new PharmacyDeliveryController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createPharmacyMock(int $id, string $name, float $deliveryFee, int $estimatedDeliveryTime, float $rating, bool $isOpen = true): Pharmacy
    {
        $pharmacy = $this->createMock(Pharmacy::class);
        $pharmacy->method('getId')->willReturn($id);
        $pharmacy->method('getName')->willReturn($name);
        $pharmacy->method('getAddress')->willReturn('123 St');
        $pharmacy->method('getDeliveryFee')->willReturn($deliveryFee);
        $pharmacy->method('getEstimatedDeliveryTime')->willReturn($estimatedDeliveryTime);
        $pharmacy->method('getRating')->willReturn($rating);
        $pharmacy->method('isOpenNow')->willReturn($isOpen);
        return $pharmacy;
    }

    private function createMedicineMock(int $id, string $name, string $genericName, float $price, string $category, bool $requiresPrescription, int $quantityInStock): Medicine
    {
        $medicine = $this->createMock(Medicine::class);
        $medicine->method('getId')->willReturn($id);
        $medicine->method('getName')->willReturn($name);
        $medicine->method('getGenericName')->willReturn($genericName);
        $medicine->method('getPrice')->willReturn($price);
        $medicine->method('getCategory')->willReturn($category);
        $medicine->method('requiresPrescription')->willReturn($requiresPrescription);
        $medicine->method('getQuantityInStock')->willReturn($quantityInStock);
        return $medicine;
    }

    // ==================== getNearbyPharmacies ====================

    public function testGetNearbyPharmacies_Success(): void
    {
        $lat = '10.0'; $lng = '106.0'; $radius = 5.0;
        $pharmacy1 = $this->createPharmacyMock(1, 'Pharma A', 20000.0, 30, 4.5, true);
        $pharmacy2 = $this->createPharmacyMock(2, 'Pharma B', 15000.0, 45, 4.0, false);

        $this->serviceMock->expects($this->once())
            ->method('getNearbyPharmacies')
            ->with(10.0, 106.0, 5.0)
            ->willReturn([$pharmacy1, $pharmacy2]);

        $request = Request::create('/api/pharmacy/nearby', 'GET', ['lat'=>$lat, 'lng'=>$lng, 'radius'=>$radius]);
        $response = $this->controller->getNearbyPharmacies($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['pharmacies']);
        $this->assertSame('Pharma A', $data['pharmacies'][0]['name']);
        $this->assertSame(20000, $data['pharmacies'][0]['delivery_fee']);
        $this->assertSame(30, $data['pharmacies'][0]['estimated_delivery_time']);
        $this->assertSame(4.5, $data['pharmacies'][0]['rating']);
        $this->assertTrue($data['pharmacies'][0]['isOpen']);
    }

    public function testGetNearbyPharmacies_MissingLat_Returns400(): void
    {
        $request = Request::create('/api/pharmacy/nearby', 'GET', ['lng' => '106.0']);
        $response = $this->controller->getNearbyPharmacies($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Lat and lng are required', $data['error']);
    }

    public function testGetNearbyPharmacies_InvalidLat_Returns400(): void
    {
        $request = Request::create('/api/pharmacy/nearby', 'GET', ['lat' => '100', 'lng' => '106.0']);
        $response = $this->controller->getNearbyPharmacies($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Invalid latitude or longitude', $data['error']);
    }

    // ==================== getPharmacyMedicines ====================

    public function testGetPharmacyMedicines_Success(): void
    {
        $pharmacyId = 5;
        $medicine1 = $this->createMedicineMock(101, 'Paracetamol', 'Acetaminophen', 5000.0, 'tablet', false, 100);
        $medicine2 = $this->createMedicineMock(102, 'Amoxicillin', 'Amoxicillin', 12000.0, 'capsule', true, 0);

        $this->serviceMock->expects($this->once())
            ->method('getPharmacyMedicines')
            ->with($pharmacyId)
            ->willReturn([$medicine1, $medicine2]);

        $response = $this->controller->getPharmacyMedicines($pharmacyId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['medicines']);
        $this->assertSame('Paracetamol', $data['medicines'][0]['name']);
        $this->assertSame('Acetaminophen', $data['medicines'][0]['genericName']);
        $this->assertSame(5000, $data['medicines'][0]['price']);
        $this->assertSame('tablet', $data['medicines'][0]['category']);
        $this->assertFalse($data['medicines'][0]['requiresPrescription']);
        $this->assertTrue($data['medicines'][0]['inStock']);

        $this->assertSame('Amoxicillin', $data['medicines'][1]['name']);
        $this->assertTrue($data['medicines'][1]['requiresPrescription']);
        $this->assertFalse($data['medicines'][1]['inStock']);
    }

    // ==================== createOrder ====================

    private function setAuthenticatedUser(string $userId = 'user-123'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
    }

    public function testCreateOrder_Success(): void
    {
        $this->setAuthenticatedUser('user-123');
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getOrderNumber')->willReturn('ORD-001');

        $this->serviceMock->expects($this->once())
            ->method('createOrder')
            ->with($this->anything())
            ->willReturn($order);

        $request = Request::create('/api/pharmacy/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'restaurantId' => 1,
            'items' => [['productId' => 10, 'quantity' => 2]],
            'deliveryAddress' => '123 Street',
            'deliveryLatitude' => 10.0,
            'deliveryLongitude' => 106.0,
            'total' => 100000,
        ]));

        $response = $this->controller->createOrder($request);
        $this->assertSame(200, $response->getStatusCode()); // Controller returns 200 OK
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(1, $data['orderId']);
        $this->assertSame('ORD-001', $data['orderNumber']);
    }

    public function testCreateOrder_ServiceException_Returns400(): void
    {
        $this->setAuthenticatedUser('user-123');
        // Simulate service validation failure
        $this->serviceMock->expects($this->once())
            ->method('createOrder')
            ->with($this->anything())
            ->willThrowException(new \Exception('Invalid order data'));

        $request = Request::create('/api/pharmacy/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'restaurantId' => 1,
            'items' => [['productId' => 10, 'quantity' => 2]],
            'deliveryAddress' => '123',
            'total' => 100000,
        ]));

        $response = $this->controller->createOrder($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertSame('Invalid order data', $data['error']);
    }

    public function testCreateOrder_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder()); // no user set
        $request = Request::create('/api/pharmacy/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'restaurantId' => 1,
            'items' => [['productId' => 10, 'quantity' => 2]],
            'deliveryAddress' => '123',
            'total' => 100000,
        ]));

        $response = $this->controller->createOrder($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== confirmOrder ====================

    public function testConfirmOrder_Success(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 1;
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);

        $this->serviceMock->expects($this->once())
            ->method('confirmOrder')
            ->with($orderId, 'user-123')
            ->willReturn($order);

        $response = $this->controller->confirmOrder($orderId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
    }

    public function testConfirmOrder_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 999;
        $this->serviceMock->expects($this->once())
            ->method('confirmOrder')
            ->with($orderId, 'user-123')
            ->willReturn(null);

        $response = $this->controller->confirmOrder($orderId);
        $this->assertSame(404, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertSame('Order not found', $data['error']);
    }

    public function testConfirmOrder_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 1;
        $this->serviceMock->expects($this->once())
            ->method('confirmOrder')
            ->with($orderId, 'user-123')
            ->willThrowException(new AccessDeniedException('Not allowed'));

        $response = $this->controller->confirmOrder($orderId);
        $this->assertSame(403, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
    }

    // ==================== prepareOrder ====================

    public function testPrepareOrder_Success(): void
    {
        $orderId = 1;
        $order = $this->createMock(Order::class);
        $this->serviceMock->expects($this->once())
            ->method('prepareOrder')
            ->with($orderId)
            ->willReturn($order);

        $response = $this->controller->prepareOrder($orderId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
    }

    public function testPrepareOrder_Invalid_Returns400(): void
    {
        $orderId = 999;
        $this->serviceMock->expects($this->once())
            ->method('prepareOrder')
            ->with($orderId)
            ->willReturn(null);

        $response = $this->controller->prepareOrder($orderId);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertSame('Invalid order', $data['error']);
    }

    // ==================== completeDelivery ====================

    public function testCompleteDelivery_Success(): void
    {
        $orderId = 1;
        $order = $this->createMock(Order::class);
        $this->serviceMock->expects($this->once())
            ->method('completeDelivery')
            ->with($orderId)
            ->willReturn($order);

        $response = $this->controller->completeDelivery($orderId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
    }

    public function testCompleteDelivery_Invalid_Returns400(): void
    {
        $orderId = 999;
        $this->serviceMock->expects($this->once())
            ->method('completeDelivery')
            ->with($orderId)
            ->willReturn(null);

        $response = $this->controller->completeDelivery($orderId);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertSame('Invalid order', $data['error']);
    }

    // ==================== cancelOrder ====================

    public function testCancelOrder_Success(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 1;
        $order = $this->createMock(Order::class);
        $this->serviceMock->expects($this->once())
            ->method('cancelOrder')
            ->with($orderId, 'user-123')
            ->willReturn($order);

        $response = $this->controller->cancelOrder($orderId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
    }

    public function testCancelOrder_NotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 999;
        $this->serviceMock->expects($this->once())
            ->method('cancelOrder')
            ->with($orderId, 'user-123')
            ->willReturn(null);

        $response = $this->controller->cancelOrder($orderId);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertSame('Cancel failed', $data['error']);
    }

    public function testCancelOrder_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('user-123');
        $orderId = 1;
        $this->serviceMock->expects($this->once())
            ->method('cancelOrder')
            ->with($orderId, 'user-123')
            ->willThrowException(new AccessDeniedException('Denied'));

        $response = $this->controller->cancelOrder($orderId);
        $this->assertSame(403, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
    }

    // ==================== getCustomerOrders ====================

    public function testGetCustomerOrders_Success(): void
    {
        $userId = 'user-123';
        $this->setAuthenticatedUser($userId);
        $now = new \DateTimeImmutable();
        $order1 = $this->createMock(Order::class);
        $order1->method('getOrderNumber')->willReturn('ORD-001');
        $order1->method('getStatus')->willReturn('pending');
        $order1->method('getTotal')->willReturn(100000);
        $order1->method('getCreatedAt')->willReturn($now);
        $order2 = $this->createMock(Order::class);
        $order2->method('getOrderNumber')->willReturn('ORD-002');
        $order2->method('getStatus')->willReturn('confirmed');
        $order2->method('getTotal')->willReturn(200000);
        $order2->method('getCreatedAt')->willReturn($now);

        $this->serviceMock->expects($this->once())
            ->method('getCustomerOrders')
            ->with($userId)
            ->willReturn([$order1, $order2]);

        $response = $this->controller->getCustomerOrders($userId);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['orders']);
        $this->assertSame('ORD-001', $data['orders'][0]['orderNumber']);
        $this->assertSame('pending', $data['orders'][0]['status']);
        $this->assertSame(100000, $data['orders'][0]['total']);
    }

    // ==================== searchMedicines ====================

    public function testSearchMedicines_Success(): void
    {
        $query = 'paracetamol';
        $pharmacyId = 5;
        $med1 = $this->createMedicineMock(1, 'Paracetamol 500mg', 'Acetaminophen', 5000, 'tablet', false, 100);
        $med2 = $this->createMedicineMock(2, 'Paracetamol 1000mg', 'Acetaminophen', 9000, 'tablet', false, 50);

        $this->serviceMock->expects($this->once())
            ->method('searchMedicines')
            ->with($query, $pharmacyId)
            ->willReturn([$med1, $med2]);

        $request = Request::create('/api/pharmacy/medicines/search', 'GET', [
            'q' => $query,
            'pharmacyId' => $pharmacyId,
        ]);
        $response = $this->controller->searchMedicines($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['medicines']);
    }

    public function testSearchMedicines_MissingQuery_Returns400(): void
    {
        $request = Request::create('/api/pharmacy/medicines/search', 'GET', [
            'pharmacyId' => 5,
        ]);
        $response = $this->controller->searchMedicines($request);
        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent() ?? '', true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Query parameter q is required', $data['error']);
    }
}

