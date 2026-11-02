<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceProduct;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceProductTest extends TestCase
{
    private function createProduct(
        string $id = 'product-id',
        string $businessId = 'business-1',
        string $name = 'Product A',
        string $category = 'Electronics',
        int $priceCents = 20000
    ): EcommerceProduct {
        return new EcommerceProduct($id, $businessId, $name, $category, $priceCents);
    }

    public function testConstructor(): void
    {
        $p = $this->createProduct();
        $this->assertSame('product-id', $p->getId());
        $this->assertSame('business-1', $p->getBusinessId());
        $this->assertSame('Product A', $p->getName());
        $this->assertNull($p->getDescription());
        $this->assertSame('Electronics', $p->getCategory());
        $this->assertSame('ACTIVE', $p->getStatus());
        $this->assertSame(20000, $p->getPriceCents());
        $this->assertSame(0, $p->getStockQuantity());
        $this->assertNull($p->getAttributes());
        $this->assertNull($p->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $p->getUpdatedAt());
        $this->assertTrue($p->isActive());
        $this->assertFalse($p->isInactive());
        $this->assertTrue($p->isOutOfStock());
        $this->assertFalse($p->isLowStock()); // stock=0 => not low stock (requires >0)
    }

    public function testSetBusinessId(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setBusinessId('business-2');
        $this->assertSame('business-2', $p->getBusinessId());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setName('New Product');
        $this->assertSame('New Product', $p->getName());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setDescription('Great product');
        $this->assertSame('Great product', $p->getDescription());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setDescription(null);
        $this->assertNull($p->getDescription());
    }

    public function testSetCategory(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setCategory('Books');
        $this->assertSame('Books', $p->getCategory());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $p->getStatus());
        $this->assertTrue($p->isInactive());
        $this->assertFalse($p->isActive());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetPriceCents(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setPriceCents(29999);
        $this->assertSame(29999, $p->getPriceCents());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetStockQuantity(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setStockQuantity(25);
        $this->assertSame(25, $p->getStockQuantity());
        $this->assertFalse($p->isOutOfStock());
        $this->assertFalse($p->isLowStock()); // 25 > 10
        $this->assertNotEquals($original, $p->getUpdatedAt());

        $p->setStockQuantity(5);
        $this->assertTrue($p->isLowStock());
        $this->assertFalse($p->isOutOfStock());

        $p->setStockQuantity(0);
        $this->assertTrue($p->isOutOfStock());
        $this->assertFalse($p->isLowStock()); // isLowStock requires >0
    }

    public function testSetAttributes(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $attrs = ['color' => 'blue', 'size' => 'M'];
        $p->setAttributes($attrs);
        $this->assertSame($attrs, $p->getAttributes());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setAttributes(null);
        $this->assertNull($p->getAttributes());
    }

    public function testSetMetadata(): void
    {
        $p = $this->createProduct();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $meta = ['vendor' => 'ABC'];
        $p->setMetadata($meta);
        $this->assertSame($meta, $p->getMetadata());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setMetadata(null);
        $this->assertNull($p->getMetadata());
    }

    public function testDecreaseStock(): void
    {
        $p = $this->createProduct();
        $p->setStockQuantity(20);
        $p->decreaseStock(5);
        $this->assertSame(15, $p->getStockQuantity());
        // go to zero
        $p->decreaseStock(15);
        $this->assertSame(0, $p->getStockQuantity());
        // decrease more should not go negative
        $p->decreaseStock(5);
        $this->assertSame(0, $p->getStockQuantity());
        $this->assertTrue($p->isOutOfStock());
    }

    public function testIncreaseStock(): void
    {
        $p = $this->createProduct();
        $p->setStockQuantity(5);
        $p->increaseStock(10);
        $this->assertSame(15, $p->getStockQuantity());
        $p->increaseStock(0);
        $this->assertSame(15, $p->getStockQuantity());
        $this->assertFalse($p->isOutOfStock());
    }

    public function testIsLowStockWithThreshold(): void
    {
        $p = $this->createProduct();
        $p->setStockQuantity(8);
        $this->assertTrue($p->isLowStock(10));
        $this->assertFalse($p->isLowStock(5)); // 8 > 5
    }
}
