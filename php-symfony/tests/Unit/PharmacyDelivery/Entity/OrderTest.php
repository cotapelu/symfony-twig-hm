<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\Order;
use App\PharmacyDelivery\Entity\Pharmacy;
use App\PharmacyDelivery\Entity\DeliveryPerson;
use App\PharmacyDelivery\Entity\OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderTest extends TestCase
{
    private function createMockPharmacy(int $id = 1): MockObject&Pharmacy
    {
        $pharmacy = $this->createMock(Pharmacy::class);
        $pharmacy->method('getId')->willReturn($id);
        return $pharmacy;
    }

    private function createMockDeliveryPerson(int $id = 1): MockObject&DeliveryPerson
    {
        $dp = $this->createMock(DeliveryPerson::class);
        $dp->method('getId')->willReturn($id);
        return $dp;
    }

    private function createOrder(?Pharmacy $pharmacy = null): Order
    {
        return new Order(); // no-arg constructor generates orderNumber and createdAt, items collection
    }

    public function testConstructorInitializesProperties(): void
    {
        $order = $this->createOrder();
        $this->assertMatchesRegularExpression('/^PHM-[A-F0-9]{12}$/', $order->getOrderNumber());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $order->getItems());
        $this->assertCount(0, $order->getItems());
        // Uninitialized non-nullable fields: pharmacy, customerId, subtotal, deliveryFee, serviceFee, tax, discount, total, deliveryAddress, deliveryLatitude, deliveryLongitude – avoid access before setting.
    }

    public function testSettersAndGetters(): void
    {
        $order = $this->createOrder();
        $pharmacy = $this->createMockPharmacy(5);
        $deliveryPerson = $this->createMockDeliveryPerson(10);

        $order->setPharmacy($pharmacy);
        $this->assertSame($pharmacy, $order->getPharmacy());

        $order->setCustomerId('cust-123');
        $this->assertSame('cust-123', $order->getCustomerId());

        $order->setDeliveryPerson($deliveryPerson);
        $this->assertSame($deliveryPerson, $order->getDeliveryPerson());

        $order->setDeliveryPerson(null);
        $this->assertNull($order->getDeliveryPerson());

        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->getStatus());

        $order->setSubtotal(100.0);
        $this->assertSame(100.0, $order->getSubtotal());

        $order->setDeliveryFee(5.0);
        $this->assertSame(5.0, $order->getDeliveryFee());

        $order->setServiceFee(2.0);
        $this->assertSame(2.0, $order->getServiceFee());

        $order->setTax(3.0);
        $this->assertSame(3.0, $order->getTax());

        $order->setDiscount(1.0);
        $this->assertSame(1.0, $order->getDiscount());

        $order->setTotal(109.0);
        $this->assertSame(109.0, $order->getTotal());

        // Items collection
        $this->assertCount(0, $order->getItems());
        $item = new OrderItem();
        // We need to set order on item? In bi-directional, addItem should set item's order.
        // But in Order entity, addItem just adds to collection without setting item's order.
        // We'll mimic typical usage: manually set item's order if needed.
        $order->addItem($item);
        $this->assertCount(1, $order->getItems());
        $this->assertTrue($order->getItems()->contains($item));

        $order->removeItem($item);
        $this->assertCount(0, $order->getItems());

        // Payment
        $payment = new \App\PharmacyDelivery\Entity\Payment(); // placeholder, but we need to test setPayment
        $order->setPayment($payment);
        $this->assertSame($payment, $order->getPayment());

        $order->setPayment(null);
        $this->assertNull($order->getPayment());

        // Delivery address and coords
        $order->setDeliveryAddress('123 Main St');
        $this->assertSame('123 Main St', $order->getDeliveryAddress());

        $order->setDeliveryLatitude(40.7128);
        $order->setDeliveryLongitude(-74.0060);
        $this->assertSame(40.7128, $order->getDeliveryLatitude());
        $this->assertSame(-74.0060, $order->getDeliveryLongitude());

        $order->setDeliveryInstructions('Ring bell');
        $this->assertSame('Ring bell', $order->getDeliveryInstructions());

        $order->setCustomerPhone('555-1234');
        $this->assertSame('555-1234', $order->getCustomerPhone());

        $order->setCustomerName('Jane Doe');
        $this->assertSame('Jane Doe', $order->getCustomerName());

        $order->setPrescriptionImageUrl('img.jpg');
        $this->assertSame('img.jpg', $order->getPrescriptionImageUrl());

        $order->setPrescriptionNumber('RX-999');
        $this->assertSame('RX-999', $order->getPrescriptionNumber());

        $order->setPatientName('John Patient');
        $this->assertSame('John Patient', $order->getPatientName());

        $order->setPatientAge(45);
        $this->assertSame(45, $order->getPatientAge());

        $order->setEstimatedDeliveryTime(30);
        $this->assertSame(30, $order->getEstimatedDeliveryTime());

        $now = new \DateTime();
        $order->setConfirmedAt($now);
        $this->assertSame($now, $order->getConfirmedAt());

        $order->setReadyAt($now);
        $this->assertSame($now, $order->getReadyAt());

        $order->setPickedUpAt($now);
        $this->assertSame($now, $order->getPickedUpAt());

        $order->setDeliveredAt($now);
        $this->assertSame($now, $order->getDeliveredAt());
    }

    public function testNullableFields(): void
    {
        $order = $this->createOrder();
        $pharmacy = $this->createMockPharmacy(1);
        $order->setPharmacy($pharmacy);
        $order->setCustomerId('c1');

        // Initially null fields
        $this->assertNull($order->getDeliveryPerson());
        $this->assertNull($order->getDeliveryInstructions());
        $this->assertNull($order->getCustomerPhone());
        $this->assertNull($order->getCustomerName());
        $this->assertNull($order->getPrescriptionImageUrl());
        $this->assertNull($order->getPrescriptionNumber());
        $this->assertNull($order->getPatientName());
        $this->assertNull($order->getPatientAge());
        $this->assertNull($order->getEstimatedDeliveryTime());
        $this->assertNull($order->getConfirmedAt());
        $this->assertNull($order->getReadyAt());
        $this->assertNull($order->getPickedUpAt());
        $this->assertNull($order->getDeliveredAt());
    }
}
