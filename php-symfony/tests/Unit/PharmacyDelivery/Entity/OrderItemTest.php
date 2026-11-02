<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\OrderItem;
use App\PharmacyDelivery\Entity\Order;
use App\PharmacyDelivery\Entity\Medicine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderItemTest extends TestCase
{
    private function createMockOrder(int $id = 1): MockObject&Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($id);
        return $order;
    }

    private function createMockMedicine(int $id = 1): MockObject&Medicine
    {
        $medicine = $this->createMock(Medicine::class);
        $medicine->method('getId')->willReturn($id);
        return $medicine;
    }

    public function testConstructor(): void
    {
        $item = new OrderItem();
        $this->assertInstanceOf(OrderItem::class, $item);
    }

    public function testGettersAndSetters(): void
    {
        $item = new OrderItem();
        $order = $this->createMockOrder(10);
        $medicine = $this->createMockMedicine(20);

        $item->setOrder($order);
        $this->assertSame($order, $item->getOrder());

        $item->setMedicine($medicine);
        $this->assertSame($medicine, $item->getMedicine());

        $item->setQuantity(5);
        $this->assertSame(5, $item->getQuantity());

        $item->setUnitPrice(7.5);
        $this->assertSame(7.5, $item->getUnitPrice());

        $item->setTotalPrice(37.5);
        $this->assertSame(37.5, $item->getTotalPrice());

        $item->setTotalPrice(0);
        $this->assertEquals(0, $item->getTotalPrice());
    }

    public function testIdSetAndGet(): void
    {
        $item = new OrderItem();
        $ref = new \ReflectionProperty($item, 'id');
        $ref->setAccessible(true);
        $ref->setValue($item, 100);
        $this->assertSame(100, $item->getId());
    }
}
