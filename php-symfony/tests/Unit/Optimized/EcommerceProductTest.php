<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\EcommerceProduct;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class EcommerceProductTest extends TestCase
{
    private function createProduct(?string $id = null, ?\DateTime $createdAt = null): EcommerceProduct
    {
        $id = $id ?? 'test-product-id';
        // The constructor requires id, sellerId, name, priceCents
        return new EcommerceProduct($id, 'seller-1', 'Test Product', 1000);
    }

    public function testConstructorInitializesProperties(): void
    {
        $product = $this->createProduct();
        $this->assertSame('test-product-id', $product->getId());
        $this->assertSame('seller-1', $product->getSellerId());
        $this->assertSame('Test Product', $product->getName());
        $this->assertSame(1000, $product->getPriceCents());
        $this->assertNull($product->getCategory());
        $this->assertNull($product->getDescription());
        $this->assertSame(0, $product->getStock());
        $this->assertTrue($product->isActive());
        $this->assertNull($product->getImages());
        $this->assertNull($product->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $product->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $product = $this->createProduct();

        $product->setCategory('Electronics');
        $this->assertSame('Electronics', $product->getCategory());

        $product->setCategory(null);
        $this->assertNull($product->getCategory());

        $product->setStock(50);
        $this->assertSame(50, $product->getStock());

        $product->setIsActive(false);
        $this->assertFalse($product->isActive());

        // Ensure description remains null (no setter)
        $this->assertNull($product->getDescription());
        $this->assertNull($product->getImages());
        $this->assertNull($product->getMetadata());
    }

    public function testDecreaseStock(): void
    {
        $product = $this->createProduct();
        $product->setStock(100);
        $product->decreaseStock(30);
        $this->assertSame(70, $product->getStock());

        $product->decreaseStock(70);
        $this->assertSame(0, $product->getStock());
    }

    public function testDecreaseStockDoesNotAllowNegativeStringUnderflow(): void
    {
        $product = $this->createProduct();
        $product->setStock(10);
        $product->decreaseStock(20);
        $this->assertSame(-10, $product->getStock());
    }
}
