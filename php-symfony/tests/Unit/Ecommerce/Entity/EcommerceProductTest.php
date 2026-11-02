<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceProduct;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceProductTest extends TestCase
{
    private function createProduct(?string $id = null, ?\DateTime $createdAt = null): EcommerceProduct
    {
        return new EcommerceProduct(createdAt: $createdAt);
    }

    public function testConstructorGeneratesIdAndSetsCreatedAt(): void
    {
        $product = $this->createProduct();
        $this->assertIsString($product->getId());
        $this->assertNotEmpty($product->getId());
        $this->assertInstanceOf(\DateTime::class, $product->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $product = $this->createProduct();

        $product->setSellerId('seller-1');
        $this->assertSame('seller-1', $product->getSellerId());

        $product->setName('Laptop');
        $this->assertSame('Laptop', $product->getName());

        $product->setDescription('High performance laptop');
        $this->assertSame('High performance laptop', $product->getDescription());

        $product->setPrice(1999.99);
        $this->assertEquals(1999.99, $product->getPrice());
        $this->assertSame(199999, $product->getPriceInCents());

        $product->setPriceInCents(9999);
        $this->assertEquals(99.99, $product->getPrice());
        $this->assertSame(9999, $product->getPriceInCents());

        $product->setCurrency('EUR');
        $this->assertSame('EUR', $product->getCurrency());

        $product->setStock(50);
        $this->assertSame(50, $product->getStock());

        $product->setImages(['img1.jpg', 'img2.jpg']);
        $this->assertSame(['img1.jpg', 'img2.jpg'], $product->getImages());

        $product->setCategory('Electronics');
        $this->assertSame('Electronics', $product->getCategory());

        $product->setTags(['new', 'sale']);
        $this->assertSame(['new', 'sale'], $product->getTags());

        $product->setIsActive(false);
        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->isActive());

        $product->deactivate();
        $this->assertFalse($product->isActive());

        $now = new \DateTime();
        $product->setUpdatedAt($now);
        $this->assertSame($now, $product->getUpdatedAt());
    }

    public function testDefaultValuesAfterConstruction(): void
    {
        $product = $this->createProduct();
        // Defaults from constructor
        $this->assertSame('USD', $product->getCurrency());
        $this->assertSame(0, $product->getPriceInCents());
        $this->assertEquals(0.0, $product->getPrice());
        $this->assertSame(0, $product->getStock());
        $this->assertTrue($product->isActive());
        $this->assertNull($product->getImages());
        $this->assertNull($product->getCategory());
        $this->assertNull($product->getTags());
        $this->assertNull($product->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $product = $this->createProduct();
        $product->setSellerId('seller-2');
        $product->setName('Phone');
        $product->setDescription('Smartphone');
        $product->setPrice(699.99);
        $product->setStock(100);
        $product->setImages(['phone.jpg']);
        $product->setCategory('Mobile');
        $product->setTags(['5G']);
        $product->setIsActive(true);

        $arr = $product->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('seller-2', $arr['sellerId']);
        $this->assertSame('Phone', $arr['name']);
        $this->assertSame('Smartphone', $arr['description']);
        $this->assertEquals(699.99, $arr['price']);
        $this->assertSame(69999, $arr['priceInCents']);
        $this->assertSame('USD', $arr['currency']);
        $this->assertSame(100, $arr['stock']);
        $this->assertSame(['phone.jpg'], $arr['images']);
        $this->assertSame('Mobile', $arr['category']);
        $this->assertSame(['5G'], $arr['tags']);
        $this->assertTrue($arr['isActive']);
    }
}
