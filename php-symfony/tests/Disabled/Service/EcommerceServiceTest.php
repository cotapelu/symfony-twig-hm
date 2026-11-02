<?php

declare(strict_types=1);

namespace App\Tests\Unit\Modules\Ecommerce\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Ecommerce\Service\EcommerceService;
use App\Ecommerce\Entity\Product;

#[AllowMockObjectsWithoutExpectations]
class EcommerceServiceTest extends TestCase
{
    private EcommerceService $service;

    protected function setUp(): void
    {
        $this->service = new EcommerceService();
    }

    public function testCreateProduct(): void
    {
        $sellerId = 123;
        $name = 'Test Product';
        $price = 50000; // in VND (integer)
        $category = 'electronics';

        $product = $this->service->createProduct($sellerId, $name, $price, $category);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame($sellerId, $product->getSellerId());
        $this->assertSame($name, $product->getName());
        $this->assertEquals($price, $product->getPrice());
        $this->assertSame($category, $product->getCategory());
        $this->assertSame('VND', $product->getCurrency());
        $this->assertSame(0, $product->getStock());
        $this->assertSame('inactive', $product->getStatus());
    }

    public function testGetProduct(): void
    {
        // Create a product first
        $product = $this->service->createProduct(1, 'Product A', 1000, 'general');
        $id = $product->getId();

        $fetched = $this->service->getProduct($id);
        $this->assertSame($product, $fetched);

        $nonExistent = $this->service->getProduct(9999);
        $this->assertNull($nonExistent);
    }

    public function testGetSellerProducts(): void
    {
        $sellerId = 42;
        $this->service->createProduct($sellerId, 'P1', 1000, 'cat1');
        $this->service->createProduct($sellerId, 'P2', 2000, 'cat2');
        $this->service->createProduct($sellerId + 1, 'P3', 3000, 'cat1'); // different seller

        $products = $this->service->getSellerProducts($sellerId, 10, 0);
        $this->assertCount(2, $products);
        foreach ($products as $p) {
            $this->assertSame($sellerId, $p->getSellerId());
        }
    }

    public function testUpdateStock(): void
    {
        $product = $this->service->createProduct(1, 'Stocked Product', 5000, 'general');
        $id = $product->getId();

        $updated = $this->service->updateStock($id, 100);
        $this->assertNotNull($updated);
        $this->assertSame(100, $updated->getStock());

        // Update again
        $this->service->updateStock($id, 50);
        $refetched = $this->service->getProduct($id);
        $this->assertSame(50, $refetched->getStock());
    }

    public function testGetActiveProducts(): void
    {
        $p1 = $this->service->createProduct(1, 'Active1', 1000, 'cat');
        $p1->activate();
        $p2 = $this->service->createProduct(1, 'Inactive', 2000, 'cat');
        $p2->deactivate();

        // Since the service uses in-memory array, activation changes object in array
        $active = $this->service->getActiveProducts(10, 0);
        $this->assertCount(1, $active);
        $this->assertSame($p1->getId(), $active[0]->getId());
    }

    public function testCountActiveProducts(): void
    {
        $this->service->createProduct(1, 'A', 1000, 'c')->activate();
        $this->service->createProduct(1, 'B', 2000, 'c')->deactivate();
        $this->service->createProduct(1, 'C', 3000, 'c')->activate();

        $count = $this->service->countActiveProducts();
        $this->assertSame(2, $count);
    }
}