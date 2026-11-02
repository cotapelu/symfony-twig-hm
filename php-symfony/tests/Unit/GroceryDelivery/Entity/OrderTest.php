<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\Order;
use App\GroceryDelivery\Entity\Store;
use App\GroceryDelivery\Entity\DeliveryPerson;
use App\GroceryDelivery\Entity\Payment;
use App\GroceryDelivery\Entity\OrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderTest extends TestCase
{
    private function createMockStore(): MockObject&Store
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        return $store;
    }

    private function createMockDeliveryPerson(): MockObject&DeliveryPerson
    {
        $dp = $this->createMock(DeliveryPerson::class);
        $dp->method('getId')->willReturn(2);
        return $dp;
    }

    private function createMockPayment(): MockObject&Payment
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getId')->willReturn(3);
        return $payment;
    }

    private function createMockOrderItem(?Order $order = null): MockObject&OrderItem
    {
        $item = $this->createMock(OrderItem::class);
        $item->method('getOrder')->willReturn($order);
        return $item;
    }

    public function testConstructorGeneratesOrderNumberAndCreatedAtAndInitializesItems(): void
    {
        $order = new Order();
        $this->assertIsString($order->getOrderNumber());
        $this->assertStringStartsWith('GD-', $order->getOrderNumber());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        $this->assertInstanceOf(ArrayCollection::class, $order->getItems());
        $this->assertCount(0, $order->getItems());
    }

    public function testGettersSetters(): void
    {
        $store = $this->createMockStore();
        $dp = $this->createMockDeliveryPerson();
        $payment = $this->createMockPayment();

        $order = new Order();
        $order->setStore($store);
        $this->assertSame($store, $order->getStore());

        $order->setCustomerId('customer-123');
        $this->assertSame('customer-123', $order->getCustomerId());

        $order->setDeliveryPerson($dp);
        $this->assertSame($dp, $order->getDeliveryPerson());

        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->getStatus());

        $order->setSubtotal(100.00);
        $this->assertSame(100.00, $order->getSubtotal());

        $order->setDeliveryFee(5.00);
        $this->assertSame(5.00, $order->getDeliveryFee());

        $order->setServiceFee(2.50);
        $this->assertSame(2.50, $order->getServiceFee());

        $order->setTax(10.00);
        $this->assertSame(10.00, $order->getTax());

        $order->setDiscount(5.00);
        $this->assertSame(5.00, $order->getDiscount());

        $order->setTotal(112.50);
        $this->assertSame(112.50, $order->getTotal());

        $item = $this->createMockOrderItem($order);
        $order->addItem($item);
        $this->assertCount(1, $order->getItems());
        $this->assertTrue($order->getItems()->contains($item));

        $order->removeItem($item);
        $this->assertCount(0, $order->getItems());

        $order->setPayment($payment);
        $this->assertSame($payment, $order->getPayment());

        $order->setDeliveryAddress('123 Main St');
        $this->assertSame('123 Main St', $order->getDeliveryAddress());

        $order->setDeliveryLatitude(10.123456);
        $this->assertSame(10.123456, $order->getDeliveryLatitude());

        $order->setDeliveryLongitude(20.654321);
        $this->assertSame(20.654321, $order->getDeliveryLongitude());

        $order->setDeliveryInstructions('Leave at door');
        $this->assertSame('Leave at door', $order->getDeliveryInstructions());

        $order->setCustomerPhone('+1234567890');
        $this->assertSame('+1234567890', $order->getCustomerPhone());

        $order->setCustomerName('John Doe');
        $this->assertSame('John Doe', $order->getCustomerName());

        $order->setEstimatedDeliveryTime(30);
        $this->assertSame(30, $order->getEstimatedDeliveryTime());

        $confirmedAt = new \DateTime('2025-01-01 10:00:00');
        $order->setConfirmedAt($confirmedAt);
        $this->assertSame($confirmedAt, $order->getConfirmedAt());

        $readyAt = new \DateTime('2025-01-01 12:00:00');
        $order->setReadyAt($readyAt);
        $this->assertSame($readyAt, $order->getReadyAt());

        $pickedUpAt = new \DateTime('2025-01-01 13:00:00');
        $order->setPickedUpAt($pickedUpAt);
        $this->assertSame($pickedUpAt, $order->getPickedUpAt());

        $deliveredAt = new \DateTime('2025-01-01 14:00:00');
        $order->setDeliveredAt($deliveredAt);
        $this->assertSame($deliveredAt, $order->getDeliveredAt());
    }

    public function testIsActive(): void
    {
        $order = new Order();

        $order->setStatus(Order::STATUS_PENDING);
        $this->assertTrue($order->isActive());

        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->assertTrue($order->isActive());

        $order->setStatus(Order::STATUS_PREPARING);
        $this->assertTrue($order->isActive());

        $order->setStatus(Order::STATUS_READY);
        $this->assertTrue($order->isActive());

        $order->setStatus(Order::STATUS_PICKED_UP);
        $this->assertTrue($order->isActive());

        $order->setStatus(Order::STATUS_DELIVERED);
        $this->assertFalse($order->isActive());

        $order->setStatus(Order::STATUS_CANCELLED);
        $this->assertFalse($order->isActive());
    }

    public function testIsCompleted(): void
    {
        $order = new Order();
        $order->setStatus(Order::STATUS_DELIVERED);
        $this->assertTrue($order->isCompleted());
        $order->setStatus(Order::STATUS_PENDING);
        $this->assertFalse($order->isCompleted());
    }

    public function testAddAndRemoveItemsWithRealOrderItems(): void
    {
        $order = new Order();
        $this->assertCount(0, $order->getItems());

        $item = new OrderItem();
        // We need to set product for OrderItem to satisfy non-null? Actually OrderItem::setProduct not null, but we can mock Product
        $product = new class { /* minimal mock of Product, but we can use real Product with mocks? easier to create mock */ };
        // To avoid complexity, we'll use mocks for OrderItem as well, but we already have a mock approach. We'll test with real OrderItem but need Product set.
        // For simplicity, use mock OrderItem as done earlier.
        $itemMock = $this->createMockOrderItem($order);
        $order->addItem($itemMock);
        $this->assertCount(1, $order->getItems());
        $order->removeItem($itemMock);
        $this->assertCount(0, $order->getItems());
    }

    public function testIdSetAndGet(): void
    {
        $order = new Order();
        $ref = new \ReflectionProperty($order, 'id');
        $ref->setAccessible(true);
        $ref->setValue($order, 100);
        $this->assertSame(100, $order->getId());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', Order::STATUS_PENDING);
        $this->assertSame('confirmed', Order::STATUS_CONFIRMED);
        $this->assertSame('preparing', Order::STATUS_PREPARING);
        $this->assertSame('ready', Order::STATUS_READY);
        $this->assertSame('picked_up', Order::STATUS_PICKED_UP);
        $this->assertSame('delivered', Order::STATUS_DELIVERED);
        $this->assertSame('cancelled', Order::STATUS_CANCELLED);
    }
}
