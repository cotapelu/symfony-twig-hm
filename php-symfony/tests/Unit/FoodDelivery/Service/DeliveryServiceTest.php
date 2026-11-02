<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\DeliveryPerson;
use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Repository\DeliveryPersonRepository;
use App\FoodDelivery\Repository\OrderRepository;
use App\FoodDelivery\Service\DeliveryService;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class DeliveryServiceTest extends TestCase
{
    private DeliveryPersonRepository $dpRepoMock;
    private OrderRepository $orderRepoMock;
    private DeliveryService $service;

    protected function setUp(): void
    {
        $this->dpRepoMock = $this->createMock(DeliveryPersonRepository::class);
        $this->orderRepoMock = $this->createMock(OrderRepository::class);
        $this->service = new DeliveryService($this->dpRepoMock, $this->orderRepoMock);
    }

    public function testRegisterDeliveryPersonSuccess(): void
    {
        $data = [
            'userId' => 'user123',
            'name' => 'John Doe',
            'vehicleType' => 'motorbike',
            'phone' => '555-1234',
            'licensePlate' => 'ABC123',
        ];

        $this->dpRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(DeliveryPerson::class));

        $result = $this->service->registerDeliveryPerson($data);

        $this->assertInstanceOf(DeliveryPerson::class, $result);
        $this->assertSame('user123', $result->getUserId());
        $this->assertSame('John Doe', $result->getName());
        $this->assertSame('motorbike', $result->getVehicleType());
        $this->assertSame('555-1234', $result->getPhone());
        $this->assertSame('ABC123', $result->getLicensePlate());
        $this->assertSame(DeliveryPerson::STATUS_OFFLINE, $result->getStatus());
        $this->assertEquals(0.0, $result->getCurrentLatitude());
        $this->assertEquals(0.0, $result->getCurrentLongitude());
    }

    public function testSetOnlineSuccess(): void
    {
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $dp->setId(5);

        $this->dpRepoMock->expects($this->once())->method('find')->with(5)->willReturn($dp);
        $this->dpRepoMock->expects($this->once())->method('save')->with($dp);

        $result = $this->service->setOnline(5, 10.0, 20.0);

        $this->assertSame($dp, $result);
        $this->assertSame(DeliveryPerson::STATUS_ONLINE, $dp->getStatus());
        $this->assertEquals(10.0, $dp->getCurrentLatitude());
        $this->assertEquals(20.0, $dp->getCurrentLongitude());
        $this->assertNotNull($dp->getLastActiveAt());
    }

    public function testSetOnlineReturnsNullIfNotFound(): void
    {
        $this->dpRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);

        $result = $this->service->setOnline(999, 10.0, 20.0);

        $this->assertNull($result);
    }

    public function testSetOfflineSuccess(): void
    {
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $dp->setId(5);

        $this->dpRepoMock->expects($this->once())->method('find')->with(5)->willReturn($dp);
        $this->dpRepoMock->expects($this->once())->method('save')->with($dp);

        $result = $this->service->setOffline(5);

        $this->assertSame($dp, $result);
        $this->assertSame(DeliveryPerson::STATUS_OFFLINE, $dp->getStatus());
    }

    public function testUpdateLocationSuccess(): void
    {
        $dp = new DeliveryPerson();
        $dp->setId(5);

        $this->dpRepoMock->expects($this->once())->method('find')->with(5)->willReturn($dp);
        $this->dpRepoMock->expects($this->once())->method('save')->with($dp);

        $this->service->updateLocation(5, 15.0, 25.0);

        $this->assertEquals(15.0, $dp->getCurrentLatitude());
        $this->assertEquals(25.0, $dp->getCurrentLongitude());
        $this->assertNotNull($dp->getLastActiveAt());
    }

    public function testAssignOrderSuccess(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $order->setId(1);

        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $dp->setId(2);

        $this->orderRepoMock->expects($this->once())->method('find')->with(1)->willReturn($order);
        $this->dpRepoMock->expects($this->once())->method('find')->with(2)->willReturn($dp);
        $this->orderRepoMock->expects($this->once())->method('save')->with($order);
        $this->dpRepoMock->expects($this->once())->method('save')->with($dp);

        $result = $this->service->assignOrder(1, 2);

        $this->assertSame($order, $result);
        $this->assertSame($dp, $order->getDeliveryPerson());
        $this->assertSame(Order::STATUS_PICKED_UP, $order->getStatus());
        $this->assertNotNull($order->getPickedUpAt());
        $this->assertSame(DeliveryPerson::STATUS_DELIVERING, $dp->getStatus());
    }

    public function testAssignOrderReturnsNullIfOrderNotFound(): void
    {
        $this->orderRepoMock->expects($this->once())->method('find')->with(1)->willReturn(null);

        $result = $this->service->assignOrder(1, 2);

        $this->assertNull($result);
    }

    public function testAssignOrderReturnsNullIfOrderStatusNotReady(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_PENDING);
        $this->orderRepoMock->method('find')->with(1)->willReturn($order);

        $result = $this->service->assignOrder(1, 2);

        $this->assertNull($result);
    }

    public function testAssignOrderReturnsNullIfDeliveryPersonNotFound(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $this->orderRepoMock->method('find')->with(1)->willReturn($order);
        $this->dpRepoMock->expects($this->once())->method('find')->with(2)->willReturn(null);

        $result = $this->service->assignOrder(1, 2);

        $this->assertNull($result);
    }

    public function testAssignOrderReturnsNullIfDeliveryPersonNotAvailable(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE);

        $this->orderRepoMock->method('find')->with(1)->willReturn($order);
        $this->dpRepoMock->method('find')->with(2)->willReturn($dp);

        $result = $this->service->assignOrder(1, 2);

        $this->assertNull($result);
    }

    public function testCompleteDeliverySuccess(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_PICKED_UP);
        $order->setId(1);

        $this->orderRepoMock->expects($this->once())->method('find')->with(1)->willReturn($order);
        $this->orderRepoMock->expects($this->once())->method('save')->with($order);

        $result = $this->service->completeDelivery(1);

        $this->assertSame($order, $result);
        $this->assertSame(Order::STATUS_DELIVERED, $order->getStatus());
        $this->assertNotNull($order->getDeliveredAt());
    }

    public function testCompleteDeliveryReturnsNullIfOrderNotFound(): void
    {
        $this->orderRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);

        $result = $this->service->completeDelivery(999);

        $this->assertNull($result);
    }

    public function testCompleteDeliveryReturnsNullIfStatusNotPickedUp(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);
        $this->orderRepoMock->method('find')->with(1)->willReturn($order);

        $result = $this->service->completeDelivery(1);

        $this->assertNull($result);
    }

    // Additional simple query tests:

    public function testFindNearbyDeliveryPersons(): void
    {
        $dps = [new DeliveryPerson(), new DeliveryPerson()];
        $this->dpRepoMock->expects($this->once())->method('findNearbyDeliveryPersons')->with(10.0, 20.0, 5.0)->willReturn($dps);

        $result = $this->service->findNearbyDeliveryPersons(10.0, 20.0, 5.0);

        $this->assertSame($dps, $result);
    }

    public function testGetAvailableDeliveryPersons(): void
    {
        $dps = [new DeliveryPerson()];
        $this->dpRepoMock->expects($this->once())->method('findAvailableDeliveryPersons')->willReturn($dps);

        $result = $this->service->getAvailableDeliveryPersons();

        $this->assertSame($dps, $result);
    }

    public function testGetDeliveryPerson(): void
    {
        $dp = new DeliveryPerson();
        $this->dpRepoMock->expects($this->once())->method('find')->with(5)->willReturn($dp);

        $result = $this->service->getDeliveryPerson(5);

        $this->assertSame($dp, $result);
    }

    public function testGetDeliveryPersonByUserId(): void
    {
        $dp = new DeliveryPerson();
        $this->dpRepoMock->expects($this->once())->method('findByUserId')->with('user123')->willReturn($dp);

        $result = $this->service->getDeliveryPersonByUserId('user123');

        $this->assertSame($dp, $result);
    }

    public function testGetDeliveryPersonOrders(): void
    {
        $orders = [new Order(), new Order()];
        $this->orderRepoMock->expects($this->once())->method('findByDeliveryPerson')->with(2)->willReturn($orders);

        $result = $this->service->getDeliveryPersonOrders(2);

        $this->assertSame($orders, $result);
    }

    // rateDelivery tests would need more setup; maybe skip for now.
}
