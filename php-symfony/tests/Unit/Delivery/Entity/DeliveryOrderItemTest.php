<?php declare(strict_types=1);

namespace App\Tests\Unit\Delivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Delivery\Entity\DeliveryOrderItem;
use App\Delivery\Entity\DeliveryOrder;
use App\Delivery\Entity\DeliveryProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderItemTest extends TestCase
{
    private function createMockOrder(int $id = 1): MockObject&DeliveryOrder
    {
        $order = $this->createMock(DeliveryOrder::class);
        $order->method('getId')->willReturn($id);
        return $order;
    }

    private function createMockProduct(int $id = 2): MockObject&DeliveryProduct
    {
        $product = $this->createMock(DeliveryProduct::class);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    private function createItem(
        ?DeliveryOrder $order = null,
        ?DeliveryProduct $product = null,
        ?int $quantity = null,
        ?int $priceCents = null
    ): DeliveryOrderItem {
        return new DeliveryOrderItem(
            order: $order ?? $this->createMockOrder(),
            product: $product ?? $this->createMockProduct(),
            quantity: $quantity ?? 1,
            priceCents: $priceCents ?? 100
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $item = $this->createItem();
        // id not set
        $this->assertInstanceOf(DeliveryOrder::class, $item->getOrder());
        $this->assertInstanceOf(DeliveryProduct::class, $item->getProduct());
        $this->assertSame(1, $item->getQuantity());
        $this->assertSame(100, $item->getPriceCents());
        $this->assertNull($item->getNotes());
    }

    public function testGettersAndSetters(): void
    {
        $order = $this->createMockOrder(10);
        $product = $this->createMockProduct(20);
        $item = $this->createItem(order: $order, product: $product, quantity: 5, priceCents: 200);

        // order and product already set, but test setter
        $newOrder = $this->createMockOrder(11);
        $item->setOrder($newOrder);
        $this->assertSame($newOrder, $item->getOrder());

        $newProduct = $this->createMockProduct(21);
        $item->setProduct($newProduct);
        $this->assertSame($newProduct, $item->getProduct());

        $item->setQuantity(3);
        $this->assertSame(3, $item->getQuantity());

        $item->setPriceCents(150);
        $this->assertSame(150, $item->getPriceCents());

        $item->setNotes('Handle with care');
        $this->assertSame('Handle with care', $item->getNotes());

        $item->setNotes(null);
        $this->assertNull($item->getNotes());
    }

    public function testGetTotalCents(): void
    {
        $item = $this->createItem(quantity: 4, priceCents: 250);
        $this->assertSame(1000, $item->getTotalCents());

        $item->setQuantity(2);
        $this->assertSame(500, $item->getTotalCents()); // priceCents still 250

        $item->setPriceCents(300);
        $this->assertSame(600, $item->getTotalCents()); // 2 * 300
    }

    public function testToArray(): void
    {
        $order = $this->createMockOrder(100);
        $product = $this->createMockProduct(200);
        $item = new DeliveryOrderItem($order, $product, 3, 400);
        // Simulate id
        $ref = new \ReflectionProperty($item, 'id');
        $ref->setAccessible(true);
        $ref->setValue($item, 500);
        $item->setNotes('Fragile');

        $arr = $item->toArray();
        $this->assertSame(500, $arr['id']);
        $this->assertSame(100, $arr['orderId']);
        $this->assertSame(200, $arr['productId']);
        $this->assertSame(3, $arr['quantity']);
        $this->assertSame(400, $arr['priceCents']);
        $this->assertSame(1200, $arr['totalCents']); // 3*400
        $this->assertSame('Fragile', $arr['notes']);
    }
}
