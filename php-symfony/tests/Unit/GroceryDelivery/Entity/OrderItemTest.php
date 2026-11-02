<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\OrderItem;
use App\GroceryDelivery\Entity\Order;
use App\GroceryDelivery\Entity\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderItemTest extends TestCase
{
    private function createMockOrder(): MockObject&Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        return $order;
    }

    private function createMockProduct(): MockObject&Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(2);
        return $product;
    }

    public function testGettersSetters(): void
    {
        $order = $this->createMockOrder();
        $product = $this->createMockProduct();

        $item = new OrderItem();

        $item->setOrder($order);
        $this->assertSame($order, $item->getOrder());

        $item->setProduct($product);
        $this->assertSame($product, $item->getProduct());

        $item->setQuantity(3);
        $this->assertSame(3, $item->getQuantity());

        $item->setUnitPrice(9.99);
        $this->assertSame(9.99, $item->getUnitPrice());
        // total should be recalculated: 9.99 * 3 = 29.97
        $this->assertSame(29.97, $item->getTotalPrice());

        // setTotalPrice directly
        $item->setTotalPrice(100.00);
        $this->assertSame(100.00, $item->getTotalPrice());

        $item->setSpecialInstructions('No salt');
        $this->assertSame('No salt', $item->getSpecialInstructions());

        $item->setSpecialInstructions(null);
        $this->assertNull($item->getSpecialInstructions());
    }

    public function testRecalculateTotalTriggersOnSetUnitPrice(): void
    {
        $order = $this->createMockOrder();
        $product = $this->createMockProduct();

        $item = new OrderItem();
        $item->setOrder($order);
        $item->setProduct($product);
        $item->setQuantity(5);

        $item->setUnitPrice(10.00);
        $this->assertSame(50.00, $item->getTotalPrice());

        // Changing quantity does NOT automatically recalc; need to setUnitPrice again or internally?
        // In entity, setQuantity does not recalc; only setUnitPrice does.
        $item->setQuantity(2);
        // Expect total still 50.00 unless we recalc manually.
        $this->assertSame(50.00, $item->getTotalPrice());
    }

    public function testSetUnitPriceRecalculatesBasedOnCurrentQuantity(): void
    {
        $order = $this->createMockOrder();
        $product = $this->createMockProduct();

        $item = new OrderItem();
        $item->setOrder($order);
        $item->setProduct($product);
        $item->setQuantity(4);
        $item->setUnitPrice(2.50);
        $this->assertSame(10.00, $item->getTotalPrice());

        // Change quantity and then unitPrice
        $item->setQuantity(5);
        $item->setUnitPrice(3.00);
        $this->assertSame(15.00, $item->getTotalPrice());
    }

    public function testIdSetAndGet(): void
    {
        $item = new OrderItem();
        $ref = new \ReflectionProperty($item, 'id');
        $ref->setAccessible(true);
        $ref->setValue($item, 999);
        $this->assertSame(999, $item->getId());
    }
}
