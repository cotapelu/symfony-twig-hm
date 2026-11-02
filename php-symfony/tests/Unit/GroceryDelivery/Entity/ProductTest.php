<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\Product;
use App\GroceryDelivery\Entity\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductTest extends TestCase
{
    private function createMockStore(): MockObject&Store
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getName')->willReturn('Test Store');
        return $store;
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $product = new Product();
        $this->assertInstanceOf(\DateTime::class, $product->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $store = $this->createMockStore();
        $product = new Product();

        $product->setStore($store);
        $this->assertSame($store, $product->getStore());

        $product->setName('Apple');
        $this->assertSame('Apple', $product->getName());

        $product->setDescription('Fresh red apple');
        $this->assertSame('Fresh red apple', $product->getDescription());

        $product->setPrice(1.99);
        $this->assertSame(1.99, $product->getPrice());

        $product->setOriginalPrice(2.50);
        $this->assertSame(2.50, $product->getOriginalPrice());

        $product->setCategory('Fruits');
        $this->assertSame('Fruits', $product->getCategory());

        $product->setUnit('kg');
        $this->assertSame('kg', $product->getUnit());

        $product->setQuantity(2.5);
        $this->assertSame(2.5, $product->getQuantity());

        $product->setImageUrl('https://example.com/apple.jpg');
        $this->assertSame('https://example.com/apple.jpg', $product->getImageUrl());

        $product->setStatus(Product::STATUS_INACTIVE);
        $this->assertSame(Product::STATUS_INACTIVE, $product->getStatus());

        $product->setStock(100);
        $this->assertSame(100, $product->getStock());

        $product->setStock(null);
        $this->assertNull($product->getStock());

        $product->setPopular(true);
        $this->assertTrue($product->isPopular());

        $product->setNew(true);
        $this->assertTrue($product->isNew());

        $product->setOnSale(true);
        $this->assertTrue($product->isOnSale());
    }

    public function testIsInStockWithStock(): void
    {
        $product = new Product();
        $product->setStock(10);
        $this->assertTrue($product->isInStock());
        $product->setStock(0);
        $this->assertFalse($product->isInStock());
    }

    public function testIsInStockWithNullStock(): void
    {
        $product = new Product();
        $product->setStock(null);
        $this->assertTrue($product->isInStock()); // null stock means unlimited?
    }

    public function testGetDiscountPercentage(): void
    {
        $product = new Product();
        $product->setPrice(80.00);
        $product->setOriginalPrice(100.00);
        $this->assertSame(20, $product->getDiscountPercentage());
    }

    public function testGetDiscountPercentageZeroWhenNoOriginal(): void
    {
        $product = new Product();
        $product->setPrice(50.00);
        $product->setOriginalPrice(null);
        $this->assertSame(0, $product->getDiscountPercentage());
    }

    public function testGetDiscountPercentageZeroWhenOriginalNotGreater(): void
    {
        $product = new Product();
        $product->setPrice(100.00);
        $product->setOriginalPrice(80.00); // lower
        $this->assertSame(0, $product->getDiscountPercentage());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('active', Product::STATUS_ACTIVE);
        $this->assertSame('inactive', Product::STATUS_INACTIVE);
        $this->assertSame('out_of_stock', Product::STATUS_OUT_OF_STOCK);
    }

    public function testIdSetAndGet(): void
    {
        $product = new Product();
        $ref = new \ReflectionProperty($product, 'id');
        $ref->setAccessible(true);
        $ref->setValue($product, 5);
        $this->assertSame(5, $product->getId());
    }
}
