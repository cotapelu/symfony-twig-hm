<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\Product;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductTest extends TestCase
{
    private function createProduct(
        ?int $sellerId = null,
        ?string $name = null,
        ?float $price = null,
        ?string $category = null
    ): Product {
        return new Product(
            sellerId: $sellerId ?? 1,
            name: $name ?? 'Test Product',
            price: $price ?? 99.99,
            category: $category ?? 'general'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $product = $this->createProduct();
        $this->assertNull($product->getId());
        $this->assertSame(1, $product->getSellerId());
        $this->assertSame('Test Product', $product->getName());
        $this->assertNull($product->getDescription());
        $this->assertSame(99.99, $product->getPrice());
        $this->assertNull($product->getSalePrice());
        $this->assertSame('VND', $product->getCurrency());
        $this->assertSame(0, $product->getStock());
        $this->assertSame('general', $product->getCategory());
        $this->assertSame([], $product->getImages());
        $this->assertSame([], $product->getAttributes());
        $this->assertSame('inactive', $product->getStatus());

    }

    public function testSettersAndGetters(): void
    {
        $product = $this->createProduct();

        $product->setId('prod-123');
        $this->assertSame('prod-123', $product->getId());

        $product->setDescription('A great product');
        $this->assertSame('A great product', $product->getDescription());

        $product->setDescription(null);
        $this->assertNull($product->getDescription());

        $product->setSalePrice(79.99);
        $this->assertSame(79.99, $product->getSalePrice());

        $product->setSalePrice(null);
        $this->assertNull($product->getSalePrice());

        $product->setStock(100);
        $this->assertSame(100, $product->getStock());

        $product->setImages(['img1.jpg', 'img2.jpg']);
        $this->assertSame(['img1.jpg', 'img2.jpg'], $product->getImages());

        $product->setAttributes(['color' => 'red', 'size' => 'M']);
        $this->assertSame(['color' => 'red', 'size' => 'M'], $product->getAttributes());

        $product->activate();
        $this->assertSame('active', $product->getStatus());

        $product->deactivate();
        $this->assertSame('inactive', $product->getStatus());
    }

    public function testToArray(): void
    {
        $product = $this->createProduct(sellerId: 5, name: 'Widget', price: 19.99, category: 'tools');
        $product->setId('prod-555');
        $product->setDescription('Useful widget');
        $product->setSalePrice(14.99);
        $product->setStock(25);
        $product->setImages(['widget.jpg']);
        $product->setAttributes(['material' => 'plastic']);
        $product->activate();

        $arr = $product->toArray();
        $this->assertSame('prod-555', $arr['id']);
        $this->assertSame(5, $arr['sellerId']);
        $this->assertSame('Widget', $arr['name']);
        $this->assertSame('Useful widget', $arr['description']);
        $this->assertSame(19.99, $arr['price']);
        $this->assertSame(14.99, $arr['salePrice']);
        $this->assertSame('VND', $arr['currency']);
        $this->assertSame(25, $arr['stock']);
        $this->assertSame('tools', $arr['category']);
        $this->assertSame(['widget.jpg'], $arr['images']);
        $this->assertSame(['material' => 'plastic'], $arr['attributes']);
        $this->assertSame('active', $arr['status']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/', $arr['createdAt']);
    }
}
