<?php declare(strict_types=1);

namespace App\Tests\Unit\Delivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Delivery\Entity\DeliveryOrder;
use App\Delivery\Entity\DeliveryStore;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderTest extends TestCase
{
    private function createMockStore(int $id = 1): MockObject&DeliveryStore
    {
        $store = $this->createMock(DeliveryStore::class);
        $store->method('getId')->willReturn($id);
        return $store;
    }

    private function createMockUser(int $id = 1): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    private function createOrder(
        ?DeliveryStore $store = null,
        ?User $customer = null,
        ?string $type = null,
        ?int $totalCents = null
    ): DeliveryOrder {
        return new DeliveryOrder(
            store: $store ?? $this->createMockStore(),
            customer: $customer ?? $this->createMockUser(),
            type: $type ?? DeliveryOrder::TYPE_FOOD,
            totalCents: $totalCents ?? 1000
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $order = $this->createOrder();
        // id is not set yet
        $this->assertSame(DeliveryOrder::TYPE_FOOD, $order->getType());
        $this->assertSame(DeliveryOrder::STATUS_PENDING, $order->getStatus());
        $this->assertNull($order->getDeliveryAddress());
        $this->assertNull($order->getDeliveryPersonId());
        $this->assertSame(1000, $order->getTotalCents());
        $this->assertNull($order->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $order->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $store = $this->createMockStore(2);
        $customer = $this->createMockUser(5);
        $order = new DeliveryOrder($store, $customer, DeliveryOrder::TYPE_GROCERY, 2500);

        $order->setStore($store);
        $this->assertSame($store, $order->getStore());

        $order->setCustomer($customer);
        $this->assertSame($customer, $order->getCustomer());

        $order->setType(DeliveryOrder::TYPE_PHARMACY);
        $this->assertSame(DeliveryOrder::TYPE_PHARMACY, $order->getType());

        $order->setStatus(DeliveryOrder::STATUS_CONFIRMED);
        $this->assertSame(DeliveryOrder::STATUS_CONFIRMED, $order->getStatus());

        $address = ['address' => '123 Main', 'city' => 'Town'];
        $order->setDeliveryAddress($address);
        $this->assertSame($address, $order->getDeliveryAddress());

        $order->setDeliveryPersonId('dp-123');
        $this->assertSame('dp-123', $order->getDeliveryPersonId());

        $order->setDeliveryPersonId(null);
        $this->assertNull($order->getDeliveryPersonId());

        $order->setTotalCents(3000);
        $this->assertSame(3000, $order->getTotalCents());

        $metadata = ['priority' => 'high'];
        $order->setMetadata($metadata);
        $this->assertSame($metadata, $order->getMetadata());

        $order->setMetadata(null);
        $this->assertNull($order->getMetadata());
    }

    public function testStatusMethods(): void
    {
        $order = $this->createOrder();
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isConfirmed());
        $this->assertFalse($order->isDelivered());
        $this->assertFalse($order->isCancelled());
        $this->assertTrue($order->canBeCancelled());

        $order->setStatus(DeliveryOrder::STATUS_CONFIRMED);
        $this->assertFalse($order->isPending());
        $this->assertTrue($order->isConfirmed());
        $this->assertTrue($order->canBeCancelled());

        $order->setStatus(DeliveryOrder::STATUS_PREPARING);
        $this->assertTrue($order->canBeCancelled());

        $order->setStatus(DeliveryOrder::STATUS_DELIVERED);
        $this->assertTrue($order->isDelivered());
        $this->assertFalse($order->canBeCancelled());

        $order->setStatus(DeliveryOrder::STATUS_CANCELLED);
        $this->assertTrue($order->isCancelled());
        $this->assertFalse($order->canBeCancelled());
    }

    public function testUpdateTimestamp(): void
    {
        $order = $this->createOrder();
        $originalUpdated = $order->getUpdatedAt();
        // Small delay to ensure time difference
        usleep(10000); // 10ms
        $order->updateTimestamp();
        $newUpdated = $order->getUpdatedAt();
        $this->assertGreaterThan($originalUpdated, $newUpdated);
    }

    public function testToArray(): void
    {
        $store = $this->createMockStore(10);
        $customer = $this->createMockUser(20);
        $order = new DeliveryOrder($store, $customer, DeliveryOrder::TYPE_PHARMACY, 1500);
        // Simulate ID
        $refId = new \ReflectionProperty($order, 'id');
        $refId->setAccessible(true);
        $refId->setValue($order, 100);
        $order->setDeliveryAddress(['lat' => 10.0, 'lng' => 20.0]);
        $order->setDeliveryPersonId('dp-99');
        $order->setMetadata(['note' => 'fragile']);

        $arr = $order->toArray();
        $this->assertSame(100, $arr['id']);
        $this->assertSame(10, $arr['storeId']);
        $this->assertSame(20, $arr['customerId']);
        $this->assertSame(DeliveryOrder::TYPE_PHARMACY, $arr['type']);
        $this->assertSame(DeliveryOrder::STATUS_PENDING, $arr['status']);
        $this->assertSame(['lat' => 10.0, 'lng' => 20.0], $arr['deliveryAddress']);
        $this->assertSame('dp-99', $arr['deliveryPersonId']);
        $this->assertSame(1500, $arr['totalCents']);
        $this->assertSame(['note' => 'fragile'], $arr['metadata']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['updatedAt']);
    }
}
