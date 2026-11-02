<?php

declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Service;

use App\PharmacyDelivery\Repository\{PharmacyRepository, MedicineRepository, OrderRepository};
use App\PharmacyDelivery\Entity\Order;
use App\PharmacyDelivery\Entity\OrderItem;
use App\PharmacyDelivery\Entity\Pharmacy;
use App\PharmacyDelivery\Entity\Medicine;
use App\PharmacyDelivery\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use App\PharmacyDelivery\Service\PharmacyDeliveryService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** Testable subclass to widen getEntityManager visibility */
class TestableOrderRepository extends OrderRepository
{
    private ?EntityManagerInterface $entityManager = null;
    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->entityManager = $em;
    }
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager ?? parent::getEntityManager();
    }
}

#[AllowMockObjectsWithoutExpectations]
class PharmacyDeliveryServiceTest extends TestCase
{
    private PharmacyRepository&MockObject $pharmacyRepo;
    private MedicineRepository&MockObject $medicineRepo;
    private OrderRepository&MockObject $orderRepo;
    private \App\PharmacyDelivery\Repository\DeliveryPersonRepository&MockObject $dpRepo;
    private \App\PharmacyDelivery\Repository\PaymentRepository&MockObject $paymentRepo;
    private PharmacyDeliveryService $service;

    protected function setUp(): void
    {
        $this->pharmacyRepo = $this->createMock(PharmacyRepository::class);
        $this->medicineRepo = $this->createMock(MedicineRepository::class);

        // Partial mock for OrderRepository: only mock find/save/findByCustomer; keep getEntityManager real via TestableOrderRepository
        $this->orderRepo = $this->getMockBuilder(TestableOrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'save', 'findByCustomer'])
            ->getMock();
        $this->dpRepo = $this->createMock(\App\PharmacyDelivery\Repository\DeliveryPersonRepository::class);
        $this->paymentRepo = $this->createMock(\App\PharmacyDelivery\Repository\PaymentRepository::class);

        // Provide a mock EntityManager for transaction tests
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        // We'll set EM later in tests that need it, but initialize to avoid null issues
        $this->orderRepo->setEntityManager($em);

        $this->service = new PharmacyDeliveryService(
            $this->orderRepo,
            $this->pharmacyRepo,
            $this->medicineRepo,
            $this->dpRepo,
            $this->paymentRepo
        );
    }

    public function testGetNearbyPharmacies(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $expected = [new \App\PharmacyDelivery\Entity\Pharmacy(), new \App\PharmacyDelivery\Entity\Pharmacy()];
        $this->pharmacyRepo->expects($this->once())
            ->method('findNearbyPharmacies')
            ->with($lat, $lng, $radius)
            ->willReturn($expected);

        $result = $this->service->getNearbyPharmacies($lat, $lng, $radius);
        $this->assertSame($expected, $result);
    }

    public function testGetNearbyPharmacies_EmptyResult(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $expected = [];
        $this->pharmacyRepo->expects($this->once())
            ->method('findNearbyPharmacies')
            ->with($lat, $lng, $radius)
            ->willReturn($expected);

        $result = $this->service->getNearbyPharmacies($lat, $lng, $radius);
        $this->assertSame($expected, $result);
    }

    public function testGetPharmacyMedicines(): void
    {
        $pharmacyId = 1;
        $expected = [new \App\PharmacyDelivery\Entity\Medicine()];
        $this->medicineRepo->expects($this->once())
            ->method('findByPharmacy')
            ->with($pharmacyId)
            ->willReturn($expected);

        $result = $this->service->getPharmacyMedicines($pharmacyId);
        $this->assertSame($expected, $result);
    }

    public function testGetPharmacyMedicines_EmptyResult(): void
    {
        $pharmacyId = 1;
        $expected = [];
        $this->medicineRepo->expects($this->once())
            ->method('findByPharmacy')
            ->with($pharmacyId)
            ->willReturn($expected);

        $result = $this->service->getPharmacyMedicines($pharmacyId);
        $this->assertSame($expected, $result);
    }

    public function testSearchMedicines(): void
    {
        $query = 'aspirin';
        $pharmacyId = 2;
        $expected = [new \App\PharmacyDelivery\Entity\Medicine()];
        $this->medicineRepo->expects($this->once())
            ->method('searchMedicines')
            ->with($query, $pharmacyId)
            ->willReturn($expected);

        $result = $this->service->searchMedicines($query, $pharmacyId);
        $this->assertSame($expected, $result);
    }

    public function testSearchMedicines_NoMatch(): void
    {
        $query = 'xyz';
        $pharmacyId = 2;
        $expected = [];
        $this->medicineRepo->expects($this->once())
            ->method('searchMedicines')
            ->with($query, $pharmacyId)
            ->willReturn($expected);

        $result = $this->service->searchMedicines($query, $pharmacyId);
        $this->assertSame($expected, $result);
    }

    public function testGetCustomerOrders(): void
    {
        $uid = 'user-123';
        $expected = [new \App\PharmacyDelivery\Entity\Order(), new \App\PharmacyDelivery\Entity\Order()];
        $this->orderRepo->expects($this->once())
            ->method('findByCustomer')
            ->with($uid)
            ->willReturn($expected);

        $result = $this->service->getCustomerOrders($uid);
        $this->assertSame($expected, $result);
    }

    public function testGetCustomerOrders_EmptyResult(): void
    {
        $uid = 'user-123';
        $expected = [];
        $this->orderRepo->expects($this->once())
            ->method('findByCustomer')
            ->with($uid)
            ->willReturn($expected);

        $result = $this->service->getCustomerOrders($uid);
        $this->assertSame($expected, $result);
    }

    // ================= confirmOrder tests =================
    public function testConfirmOrderSuccess(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PENDING);
        $order->method('getCustomerId')->willReturn($userId);
        $order->expects($this->once())
            ->method('setStatus')
            ->with(Order::STATUS_CONFIRMED)
            ->willReturn($order);
        $order->expects($this->once())
            ->method('setConfirmedAt')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn($order);

        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $result = $this->service->confirmOrder($orderId, $userId);
        $this->assertSame($order, $result);
    }

    public function testConfirmOrder_NotFound(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn(null);
        $result = $this->service->confirmOrder($orderId, $userId);
        $this->assertNull($result);
    }

    public function testConfirmOrder_WrongStatus(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_CONFIRMED);
        $order->method('getCustomerId')->willReturn($userId);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->confirmOrder($orderId, $userId);
        $this->assertNull($result);
    }

    // ================= readyOrder tests =================
    public function testReadyOrderSuccess(): void
    {
        $orderId = 123;
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PREPARING);
        $order->expects($this->once())
            ->method('setStatus')
            ->with(Order::STATUS_READY)
            ->willReturn($order);
        $order->expects($this->once())
            ->method('setReadyAt')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn($order);

        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $result = $this->service->readyOrder($orderId);
        $this->assertSame($order, $result);
    }

    public function testReadyOrder_NotFound(): void
    {
        $orderId = 123;
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn(null);
        $result = $this->service->readyOrder($orderId);
        $this->assertNull($result);
    }

    public function testReadyOrder_WrongStatus(): void
    {
        $orderId = 123;
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PENDING);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->readyOrder($orderId);
        $this->assertNull($result);
    }

    // ================= cancelOrder tests =================
    public function testCancelOrderSuccess(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($userId);
        $order->method('getStatus')->willReturn(Order::STATUS_PENDING);
        $order->method('isActive')->willReturn(true);
        $order->method('getItems')->willReturn([]);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_CANCELLED);

        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $this->medicineRepo->expects($this->never())->method('save');

        $result = $this->service->cancelOrder($orderId, $userId);
        $this->assertSame($order, $result);
    }

    public function testCancelOrder_NotFound(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn(null);
        $result = $this->service->cancelOrder($orderId, $userId);
        $this->assertNull($result);
    }

    public function testCancelOrder_Inactive(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($userId);
        $order->method('isActive')->willReturn(false);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->cancelOrder($orderId, $userId);
        $this->assertNull($result);
    }

    // ================= processPayment tests =================
    public function testProcessPaymentSuccess(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($userId);
        $order->method('getTotal')->willReturn(100.0);
        $order->method('getSubtotal')->willReturn(80.0);
        $order->method('getDeliveryFee')->willReturn(10.0);
        $order->method('getPayment')->willReturn(null);

        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        // We'll expect two saves on paymentRepo and one on orderRepo
        $this->paymentRepo->expects($this->exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(Payment::class));
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo->setEntityManager($em);
        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->once())->method('commit');
        $em->expects($this->never())->method('rollback');

        $result = $this->service->processPayment($orderId, ['method' => 'card'], $userId);
        $this->assertInstanceOf(Payment::class, $result);
    }

    public function testProcessPayment_OrderNotFound(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn(null);
        $result = $this->service->processPayment($orderId, [], $userId);
        $this->assertNull($result);
    }

    public function testProcessPayment_ExistingCompleted_ReturnsExisting(): void
    {
        $orderId = 123;
        $userId = 'user-123';
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($userId);
        $existingPayment = $this->createMock(Payment::class);
        $existingPayment->method('getStatus')->willReturn(Payment::STATUS_COMPLETED);
        $order->method('getPayment')->willReturn($existingPayment);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->paymentRepo->expects($this->never())->method('save');
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->processPayment($orderId, [], $userId);
        $this->assertSame($existingPayment, $result);
    }
}
