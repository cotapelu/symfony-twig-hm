<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\DeliveryPerson;
use App\GroceryDelivery\Entity\Order;
use App\GroceryDelivery\Repository\DeliveryPersonRepository;
use App\GroceryDelivery\Repository\OrderRepository;
use App\GroceryDelivery\Service\DeliveryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryServiceTest extends TestCase
{
    private DeliveryPersonRepository&MockObject $dpRepo;
    private OrderRepository&MockObject $orderRepo;
    private DeliveryService $service;

    protected function setUp(): void
    {
        $this->dpRepo = $this->createMock(DeliveryPersonRepository::class);
        $this->orderRepo = $this->createMock(OrderRepository::class);
        $this->service = new DeliveryService($this->dpRepo, $this->orderRepo);
    }

    public function testSetOnline(): void
    {
        $dpId = 1;
        $lat = 10.0;
        $lng = 20.0;
        $dp = new DeliveryPerson();
        $dp->setId($dpId);
        $this->dpRepo->expects($this->once())->method('find')->with($dpId)->willReturn($dp);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $result = $this->service->setOnline($dpId, $lat, $lng);
        $this->assertSame($dp, $result);
        $this->assertSame(DeliveryPerson::STATUS_ONLINE, $dp->getStatus());
        $this->assertSame($lat, $dp->getCurrentLatitude());
        $this->assertSame($lng, $dp->getCurrentLongitude());
        $this->assertNotNull($dp->getLastActiveAt());
    }

    public function testSetOnlineWhenNotFound(): void
    {
        $this->dpRepo->expects($this->once())->method('find')->with(999)->willReturn(null);
        $this->dpRepo->expects($this->never())->method('save');
        $result = $this->service->setOnline(999, 1.0, 2.0);
        $this->assertNull($result);
    }

    public function testSetOffline(): void
    {
        $dpId = 2;
        $dp = new DeliveryPerson();
        $dp->setId($dpId);
        $this->dpRepo->expects($this->once())->method('find')->with($dpId)->willReturn($dp);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $result = $this->service->setOffline($dpId);
        $this->assertSame($dp, $result);
        $this->assertSame(DeliveryPerson::STATUS_OFFLINE, $dp->getStatus());
    }

    public function testSetOfflineWhenNotFound(): void
    {
        $this->dpRepo->expects($this->once())->method('find')->with(999)->willReturn(null);
        $this->dpRepo->expects($this->never())->method('save');
        $result = $this->service->setOffline(999);
        $this->assertNull($result);
    }

    public function testAssignOrder(): void
    {
        $orderId = 10;
        $dpId = 20;
        $order = new Order();
        $order->setId($orderId);
        $order->setStatus(Order::STATUS_READY);
        $dp = new DeliveryPerson();
        $dp->setId($dpId);
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $dp->setActive(true);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->with($dpId)->willReturn($dp);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $result = $this->service->assignOrder($orderId, $dpId);
        $this->assertSame($order, $result);
        $this->assertSame(Order::STATUS_PICKED_UP, $order->getStatus());
        $this->assertSame($dp, $order->getDeliveryPerson());
        $this->assertSame(DeliveryPerson::STATUS_BUSY, $dp->getStatus());
    }

    public function testAssignOrderWhenOrderNotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->with(10)->willReturn(null);
        // Service calls find on delivery person as well (no short-circuit)
        $this->dpRepo->expects($this->once())->method('find')->with(20)->willReturn(null);
        $result = $this->service->assignOrder(10, 20);
        $this->assertNull($result);
    }

    public function testAssignOrderWhenDeliveryPersonNotFound(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $this->orderRepo->expects($this->once())->method('find')->with(10)->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->with(20)->willReturn(null);
        $result = $this->service->assignOrder(10, 20);
        $this->assertNull($result);
    }

    public function testAssignOrderWhenOrderNotReady(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_PENDING);
        $dp = new DeliveryPerson();
        $this->orderRepo->expects($this->once())->method('find')->with(10)->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->with(20)->willReturn($dp);
        $result = $this->service->assignOrder(10, 20);
        $this->assertNull($result);
    }

    public function testAssignOrderWhenDeliveryPersonNotAvailable(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE); // not online
        $dp->setActive(true);
        $this->orderRepo->expects($this->once())->method('find')->with(10)->willReturn($order);
        $this->dpRepo->expects($this->once())->method('find')->with(20)->willReturn($dp);
        $result = $this->service->assignOrder(10, 20);
        $this->assertNull($result);
    }

    public function testCompleteDelivery(): void
    {
        $orderId = 100;
        $dpId = 200;
        $order = new Order();
        $order->setId($orderId);
        $order->setStatus(Order::STATUS_PICKED_UP);
        $dp = new DeliveryPerson();
        $dp->setId($dpId);
        $dp->setCompletedDeliveries(5);
        $order->setDeliveryPerson($dp);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $this->dpRepo->expects($this->once())->method('save')->with($dp);
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        $result = $this->service->completeDelivery($orderId);
        $this->assertSame($order, $result);
        $this->assertSame(Order::STATUS_DELIVERED, $order->getStatus());
        $this->assertSame(DeliveryPerson::STATUS_ONLINE, $dp->getStatus());
        $this->assertSame(6, $dp->getCompletedDeliveries());
    }

    public function testCompleteDeliveryWhenOrderNotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->with(100)->willReturn(null);
        $this->dpRepo->expects($this->never())->method('save');
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->completeDelivery(100);
        $this->assertNull($result);
    }

    public function testCompleteDeliveryWhenWrongStatus(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $this->orderRepo->expects($this->once())->method('find')->with(100)->willReturn($order);
        $result = $this->service->completeDelivery(100);
        $this->assertNull($result);
    }

    public function testFindNearby(): void
    {
        $lat = 5.0;
        $lng = 10.0;
        $r = 3.0;
        $drivers = [new DeliveryPerson(), new DeliveryPerson()];
        $this->dpRepo->expects($this->once())->method('findNearby')->with($lat, $lng, $r)->willReturn($drivers);
        $result = $this->service->findNearby($lat, $lng, $r);
        $this->assertSame($drivers, $result);
    }
}
