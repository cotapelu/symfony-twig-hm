<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryProduct;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryProductTest extends TestCase
{
    private function createProduct(
        string $id = 'product-id',
        string $storeId = 'store-1',
        string $category = 'Food',
        string $name = 'Pizza',
        int $priceCents = 1000,
        int $stock = 50
    ): DeliveryProduct {
        return new DeliveryProduct($id, $storeId, $category, $name, $priceCents, $stock);
    }

    public function testConstructor(): void
    {
        $p = $this->createProduct();
        $this->assertSame('product-id', $p->getId());
        $this->assertSame('store-1', $p->getStoreId());
        $this->assertSame('Food', $p->getCategory());
        $this->assertSame('Pizza', $p->getName());
        $this->assertNull($p->getDescription());
        $this->assertSame(1000, $p->getPriceCents());
        $this->assertSame(50, $p->getStock());
        $this->assertTrue($p->isActive());
        $this->assertNull($p->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $p->getUpdatedAt());
        $this->assertTrue($p->isInStock());
        $this->assertFalse($p->isOutOfStock());
        $this->assertFalse($p->isLowStock());
    }

    public function testSetStoreId(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setStoreId('store-2');
        $this->assertSame('store-2', $p->getStoreId());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetCategory(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setCategory('Drink');
        $this->assertSame('Drink', $p->getCategory());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setName('Burger');
        $this->assertSame('Burger', $p->getName());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setDescription('Delicious');
        $this->assertSame('Delicious', $p->getDescription());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setDescription(null);
        $this->assertNull($p->getDescription());
    }

    public function testSetPriceCents(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setPriceCents(2500);
        $this->assertSame(2500, $p->getPriceCents());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetStock(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setStock(5);
        $this->assertSame(5, $p->getStock());
        $this->assertTrue($p->isLowStock());
        $this->assertTrue($p->isInStock());
        $this->assertFalse($p->isOutOfStock());

        $p->setStock(0);
        $this->assertFalse($p->isInStock());
        $this->assertTrue($p->isOutOfStock());
        $this->assertFalse($p->isLowStock());

        $p->setStock(15);
        $this->assertTrue($p->isInStock());
        $this->assertFalse($p->isOutOfStock());
        $this->assertFalse($p->isLowStock());
    }

    public function testSetActive(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setActive(false);
        $this->assertFalse($p->isActive());
        $this->assertTrue($p->isOutOfStock()); // inactive => out of stock
        $this->assertFalse($p->isInStock());
        $this->assertNotEquals($original, $p->getUpdatedAt());

        $p->setActive(true);
        $this->assertTrue($p->isActive());
        $this->assertTrue($p->isInStock()); // stock 50 >0
    }

    public function testSetMetadata(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $meta = ['tags' => ['spicy', 'new']];
        $p->setMetadata($meta);
        $this->assertSame($meta, $p->getMetadata());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setMetadata(null);
        $this->assertNull($p->getMetadata());
    }
}
