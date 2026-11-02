<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryOrderItem;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderItemTest extends TestCase
{
    private function createItem(
        string $id = 'item-id',
        string $orderId = 'order-1',
        string $productId = 'product-1',
        int $quantity = 2,
        int $priceCents = 500
    ): DeliveryOrderItem {
        return new DeliveryOrderItem($id, $orderId, $productId, $quantity, $priceCents);
    }

    public function testConstructor(): void
    {
        $i = $this->createItem();
        $this->assertSame('item-id', $i->getId());
        $this->assertSame('order-1', $i->getOrderId());
        $this->assertSame('product-1', $i->getProductId());
        $this->assertSame(2, $i->getQuantity());
        $this->assertSame(500, $i->getPriceCents());
        $this->assertNull($i->getNotes());
        $this->assertSame(1000, $i->getTotalCents());
        $this->assertTrue($i->isAvailable());
        $this->assertFalse($i->isOutOfStock());
    }

    public function testSetOrderId(): void
    {
        $i = $this->createItem();
        $i->setOrderId('order-2');
        $this->assertSame('order-2', $i->getOrderId());
    }

    public function testSetProductId(): void
    {
        $i = $this->createItem();
        $i->setProductId('product-2');
        $this->assertSame('product-2', $i->getProductId());
    }

    public function testSetQuantity(): void
    {
        $i = $this->createItem();
        $i->setQuantity(0);
        $this->assertSame(0, $i->getQuantity());
        $this->assertFalse($i->isAvailable());
        $this->assertTrue($i->isOutOfStock());

        $i->setQuantity(5);
        $this->assertTrue($i->isAvailable());
        $this->assertFalse($i->isOutOfStock());
    }

    public function testSetPriceCents(): void
    {
        $i = $this->createItem();
        $i->setPriceCents(800);
        $this->assertSame(800, $i->getPriceCents());
    }

    public function testSetNotes(): void
    {
        $i = $this->createItem();
        $i->setNotes('Fragile');
        $this->assertSame('Fragile', $i->getNotes());
        $i->setNotes(null);
        $this->assertNull($i->getNotes());
    }

    public function testGetTotalCents(): void
    {
        $i = $this->createItem();
        $this->assertSame(1000, $i->getTotalCents()); // 2 * 500

        $i->setQuantity(3);
        $this->assertSame(1500, $i->getTotalCents());

        $i->setPriceCents(1000);
        $this->assertSame(3000, $i->getTotalCents());
    }

    public function testIsAvailableAndOutOfStock(): void
    {
        $i = $this->createItem();
        $i->setQuantity(0);
        $this->assertFalse($i->isAvailable());
        $this->assertTrue($i->isOutOfStock());

        $i->setQuantity(1);
        $this->assertTrue($i->isAvailable());
        $this->assertFalse($i->isOutOfStock());
    }
}
