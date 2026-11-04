<?php

declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Service;

use App\PharmacyDelivery\Repository\{PharmacyRepository, MedicineRepository, OrderRepository};
use App\PharmacyDelivery\Entity\Order;
use App\PharmacyDelivery\Entity\OrderItem;
use App\PharmacyDelivery\Entity\Pharmacy;
use App\PharmacyDelivery\Entity\Medicine;
use App\PharmacyDelivery\Entity\Payment;
use App\PharmacyDelivery\Entity\DeliveryPerson;
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

    public function testProcessPayment_WrongUser(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn('other-user');
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->service->processPayment(1, [], 'user-123');
    }

    // ================= prepareOrder tests =================
    public function testPrepareOrderSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_CONFIRMED);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_PREPARING)->willReturn($order);
        $order->expects($this->once())->method('setReadyAt')->with($this->isInstanceOf(\DateTime::class))->willReturn($order);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $this->assertSame($order, $this->service->prepareOrder(1));
    }

    public function testPrepareOrder_NotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->willReturn(null);
        $this->assertNull($this->service->prepareOrder(1));
    }

    public function testPrepareOrder_WrongStatus(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PENDING);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $this->assertNull($this->service->prepareOrder(1));
    }

    // ================= ownership negative tests =================
    public function testConfirmOrder_WrongUser(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn('other');
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->service->confirmOrder(1, 'user-123');
    }

    public function testConfirmOrder_FromWaitingPrescription(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn('user-123');
        $order->method('getStatus')->willReturn(Order::STATUS_WAITING_PRESCRIPTION);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_CONFIRMED)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $this->assertSame($order, $this->service->confirmOrder(1, 'user-123'));
    }

    public function testCancelOrder_WrongUser(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn('other');
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->service->cancelOrder(1, 'user-123');
    }

    public function testCancelOrder_RestocksMedicines(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn('user-123');
        $order->method('isActive')->willReturn(true);
        $medicine = new Medicine();
        $medicine->setQuantityInStock(8);
        $item = $this->createMock(OrderItem::class);
        $item->method('getMedicine')->willReturn($medicine);
        $item->method('getQuantity')->willReturn(3);
        $order->method('getItems')->willReturn([$item]);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_CANCELLED);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->medicineRepo->expects($this->once())->method('save')->with($this->callback(
            fn (Medicine $m) => $m->getQuantityInStock() === 11
        ));
        $this->service->cancelOrder(1, 'user-123');
    }

    // ================= assignDeliveryPerson tests =================
    public function testAssignDeliveryPersonSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_READY);
        $dp = new DeliveryPerson();
        $dp->setStatus('available')->setTotalDeliveries(2);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->willReturn($dp);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->exactly(2))->method('lock');
        $em->expects($this->once())->method('commit');
        $em->expects($this->never())->method('rollback');
        $this->orderRepo->setEntityManager($em);
        $order->expects($this->once())->method('setDeliveryPerson')->with($dp)->willReturn($order);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_PICKED_UP)->willReturn($order);
        $order->expects($this->once())->method('setPickedUpAt')->willReturn($order);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $result = $this->service->assignDeliveryPerson(1, 5);
        $this->assertSame($order, $result);
        $this->assertSame('busy', $dp->getStatus());
        $this->assertSame(3, $dp->getTotalDeliveries());
    }

    public function testAssignDeliveryPerson_NotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->willReturn(null);
        $this->dpRepo->expects($this->once())->method('find')->willReturn(null);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->once())->method('rollback');
        $em->expects($this->never())->method('commit');
        $this->orderRepo->setEntityManager($em);
        $this->assertNull($this->service->assignDeliveryPerson(1, 5));
    }

    public function testAssignDeliveryPerson_DriverUnavailable(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_READY);
        $dp = new DeliveryPerson();
        $dp->setStatus('busy');
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->willReturn($dp);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('rollback');
        $em->expects($this->never())->method('commit');
        $this->orderRepo->setEntityManager($em);
        $this->assertNull($this->service->assignDeliveryPerson(1, 5));
    }

    public function testAssignDeliveryPerson_OrderNotReady(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PENDING);
        $dp = new DeliveryPerson();
        $dp->setStatus('available');
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->willReturn($dp);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('rollback');
        $this->orderRepo->setEntityManager($em);
        $this->assertNull($this->service->assignDeliveryPerson(1, 5));
    }

    // ================= completeDelivery tests =================
    public function testCompleteDeliverySuccess_WithDriver(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PICKED_UP);
        $dp = new DeliveryPerson();
        $dp->setStatus('busy')->setTotalDeliveries(4);
        $order->method('getDeliveryPerson')->willReturn($dp);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_DELIVERED)->willReturn($order);
        $order->expects($this->once())->method('setDeliveredAt')->willReturn($order);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('commit');
        $this->orderRepo->setEntityManager($em);

        $this->assertSame($order, $this->service->completeDelivery(1));
        $this->assertSame('available', $dp->getStatus());
        $this->assertSame(5, $dp->getTotalDeliveries());
    }

    public function testCompleteDeliverySuccess_NoDriver(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_PICKED_UP);
        $order->method('getDeliveryPerson')->willReturn(null);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_DELIVERED)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $this->dpRepo->expects($this->never())->method('save');
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo->setEntityManager($em);
        $this->assertSame($order, $this->service->completeDelivery(1));
    }

    public function testCompleteDelivery_WrongStatus(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(Order::STATUS_CONFIRMED);
        $this->orderRepo->expects($this->once())->method('find')->willReturn($order);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('rollback');
        $this->orderRepo->setEntityManager($em);
        $this->assertNull($this->service->completeDelivery(1));
    }

    // ================= createOrder: success + fee math =================
    private function makeActivePharmacy(): Pharmacy
    {
        $p = new Pharmacy();
        $p->setName('Pharmacy A')->setDeliveryFee(5.0)->setEstimatedDeliveryTime(30);
        return $p;
    }

    private function makeMedicine(string $id = 'med-1', bool $prescription = false): Medicine
    {
        $m = new Medicine();
        $m->setName('Aspirin')->setPrice(10.5)->setQuantityInStock(20);
        // typed property has no default: initialize both branches
        $m->setRequiresPrescription($prescription);
        return $m;
    }

    /** @return array<string, mixed> */
    private function validOrderData(array $overrides = []): array
    {
        return array_merge([
            'customerId' => 'cust-1',
            'pharmacyId' => 'ph-1',
            'items' => [['medicineId' => 'med-1', 'quantity' => 2]],
            'deliveryAddress' => '123 Nguyen Hue',
            'lat' => 10.7769,
            'lng' => 106.7009,
        ], $overrides);
    }

    public function testCreateOrderSuccess_ComputesFeesAndReservesStock(): void
    {
        $pharmacy = $this->makeActivePharmacy();
        $medicine = $this->makeMedicine();
        $this->pharmacyRepo->method('find')->willReturn($pharmacy);
        $this->medicineRepo->method('find')->willReturn($medicine);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->once())->method('commit');
        $em->expects($this->never())->method('rollback');
        $this->orderRepo->setEntityManager($em);
        $this->orderRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Order::class));
        // reserveStock(): 20 - 2 = 18
        $this->medicineRepo->expects($this->once())->method('save')->with($this->callback(
            fn (Medicine $m) => $m->getQuantityInStock() === 18
        ));

        $order = $this->service->createOrder($this->validOrderData());

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEqualsWithDelta(21.0, $order->getSubtotal(), 0.001);
        $this->assertEqualsWithDelta(5.0, $order->getDeliveryFee(), 0.001);
        $this->assertEqualsWithDelta(0.42, $order->getServiceFee(), 0.001); // 2%
        $this->assertEqualsWithDelta(1.05, $order->getTax(), 0.001);        // 5%
        $this->assertEqualsWithDelta(27.47, $order->getTotal(), 0.001);
        $this->assertSame(Order::STATUS_PENDING, $order->getStatus());
        $this->assertCount(1, $order->getItems());
    }

    public function testCreateOrder_SanitizesDeliveryAddress(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine());
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo->setEntityManager($em);

        $order = $this->service->createOrder($this->validOrderData([
            'deliveryAddress' => '<script>alert(1)</script>',
        ]));
        // XSS guard: address is html-escaped on persistence
        $this->assertSame(
            htmlspecialchars('<script>alert(1)</script>', ENT_QUOTES, 'UTF-8'),
            $order->getDeliveryAddress()
        );
    }

    public function testCreateOrder_RollsBackAndRethrowsWhenSaveFails(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine());
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->once())->method('rollback');
        $em->expects($this->never())->method('commit');
        $this->orderRepo->setEntityManager($em);
        $this->orderRepo->method('save')->willThrowException(new \RuntimeException('db down'));

        $this->expectException(\RuntimeException::class);
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_PrescriptionRequired_MissingImage(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine('med-1', true));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prescription image is required');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_PrescriptionImage_InvalidUrl(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine('med-1', true));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prescription image URL is invalid');
        $this->service->createOrder($this->validOrderData(['prescriptionImageUrl' => 'not-a-url']));
    }

    public function testCreateOrder_PrescriptionAccepted_WaitingStatus(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine('med-1', true));
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo->setEntityManager($em);

        $order = $this->service->createOrder($this->validOrderData([
            'prescriptionImageUrl' => 'https://cdn.example.com/rx.jpg',
            'patientName' => 'Nguyen Van A',
            'patientAge' => '42',
        ]));

        $this->assertSame(Order::STATUS_WAITING_PRESCRIPTION, $order->getStatus());
        $this->assertSame('https://cdn.example.com/rx.jpg', $order->getPrescriptionImageUrl());
        $this->assertSame('Nguyen Van A', $order->getPatientName());
        $this->assertSame(42, $order->getPatientAge());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCreateOrderPayloads')]
    public function testCreateOrder_ValidationErrors(array $payload, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->service->createOrder($payload);
    }

    public static function invalidCreateOrderPayloads(): array
    {
        $base = [
            'customerId' => 'cust-1',
            'pharmacyId' => 'ph-1',
            'items' => [['medicineId' => 'med-1', 'quantity' => 2]],
            'deliveryAddress' => '123 Nguyen Hue',
            'lat' => 10.7,
            'lng' => 106.7,
        ];
        return [
            'missing customerId' => [array_diff_key($base, ['customerId' => 1]), 'Customer ID is required'],
            'missing pharmacyId' => [array_diff_key($base, ['pharmacyId' => 1]), 'Pharmacy ID is required'],
            'empty items' => [array_merge($base, ['items' => []]), 'Items must be a non-empty array'],
            'items not array' => [array_merge($base, ['items' => 'x']), 'Items must be a non-empty array'],
            'missing address' => [array_merge($base, ['deliveryAddress' => '   ']), 'Delivery address is required'],
            'missing coords' => [array_diff_key($base, ['lat' => 1, 'lng' => 1]), 'Latitude and longitude are required'],
            'coords out of range' => [array_merge($base, ['lat' => 120]), 'Invalid latitude or longitude'],
        ];
    }

    public function testCreateOrder_PharmacyNotFound(): void
    {
        $this->pharmacyRepo->method('find')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pharmacy invalid or inactive');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_PharmacyInactive(): void
    {
        $pharmacy = $this->makeActivePharmacy();
        $pharmacy->setStatus('inactive');
        $this->pharmacyRepo->method('find')->willReturn($pharmacy);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pharmacy invalid or inactive');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_ItemInvalid(): void
    {
        // service order: basic → latlng → pharmacy → items; provide a valid pharmacy
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Each item must have medicineId and positive quantity');
        $this->service->createOrder($this->validOrderData([
            'items' => [['medicineId' => 'med-1', 'quantity' => 0]],
        ]));
    }

    public function testCreateOrder_MedicineNotFound(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Medicine not found: med-1');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_MedicineUnavailable(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine()->setIsAvailable(false));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Medicine unavailable');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_InsufficientStock(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine()->setQuantityInStock(1));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');
        $this->service->createOrder($this->validOrderData());
    }

    public function testCreateOrder_NegativeDiscount(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount cannot be negative');
        $this->service->createOrder($this->validOrderData(['discount' => -5.0]));
    }

    public function testCreateOrder_DiscountExceedsSubtotal(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount cannot exceed subtotal');
        $this->service->createOrder($this->validOrderData(['discount' => 100.0]));
    }

    public function testCreateOrder_DiscountApplied(): void
    {
        $this->pharmacyRepo->method('find')->willReturn($this->makeActivePharmacy());
        $this->medicineRepo->method('find')->willReturn($this->makeMedicine());
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo->setEntityManager($em);

        $order = $this->service->createOrder($this->validOrderData(['discount' => 1.0]));
        $this->assertEqualsWithDelta(1.0, $order->getDiscount(), 0.001);
        $this->assertEqualsWithDelta(26.47, $order->getTotal(), 0.001);
    }
}
