<?php

declare(strict_types=1);

namespace App\Tests\Service\Ecommerce;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\Product;
use App\Ecommerce\Service\EcommerceService;
use App\Tests\TestCase;

/** @skip Old in-memory tests need update for repository pattern */
#[AllowMockObjectsWithoutExpectations]
class EcommerceServiceTest extends TestCase
{
    private EcommerceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EcommerceService();
    }

    public function testCreateProductAddsToList(): void
    {
        $product = $this->service->createProduct(
            sellerId: 1,
            name: 'Test Product',
            price: 100.0,
            category: 'electronics'
        );

        $this->assertNotNull($product);
        $this->assertEquals(1, $product->getId());
        $this->assertSame(1, $product->getSellerId());
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(100.0, $product->getPrice());
        $this->assertEquals('electronics', $product->getCategory());
    }

    public function testGetProductReturnsExisting(): void
    {
        $created = $this->service->createProduct(1, 'Product A', 100, 'books');
        $found = $this->service->getProduct($created->getId());
        $this->assertSame($created, $found);
    }

    public function testGetProductReturnsNullIfNotFound(): void
    {
        $result = $this->service->getProduct(9999);
        $this->assertNull($result);
    }

    public function testGetSellerProductsReturnsOnlyOwned(): void
    {
        $this->service->createProduct(1, 'P1', 10, 'cat1');
        $this->service->createProduct(2, 'P2', 20, 'cat1');
        $this->service->createProduct(1, 'P3', 30, 'cat2');

        $seller1 = $this->service->getSellerProducts(1);
        $this->assertCount(2, $seller1);
        $this->assertEquals(['P1', 'P3'], array_map(fn($p) => $p->getName(), $seller1));
    }

    public function testGetSellerProductsHonorsLimitAndOffset(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->createProduct(1, "Product $i", 10 * $i, 'cat');
        }

        $page = $this->service->getSellerProducts(1, limit: 2, offset: 2);
        $this->assertCount(2, $page);
        $this->assertEquals('Product 2', $page[0]->getName());
        $this->assertEquals('Product 3', $page[1]->getName());
    }

    public function testGetProductsByCategoryFiltersCorrectly(): void
    {
        $this->service->createProduct(1, 'A', 10, 'electronics');
        $this->service->createProduct(1, 'B', 20, 'books');
        $this->service->createProduct(1, 'C', 30, 'electronics');

        $electronics = $this->service->getProductsByCategory('electronics');
        $this->assertCount(2, $electronics);
        $this->assertEquals(['A', 'C'], array_map(fn($p) => $p->getName(), $electronics));
    }

    public function testSearchProductsMatchesNameAndDescription(): void
    {
        $this->service->createProduct(1, 'Red Shirt', 50, 'clothing', 'A bright red shirt');
        $this->service->createProduct(1, 'Blue Jeans', 100, 'clothing', 'Denim jeans');
        $this->service->createProduct(1, 'Red Shoes', 80, 'footwear', 'Comfortable red shoes');

        $results = $this->service->searchProducts('red');
        $this->assertCount(2, $results); // Red Shirt and Red Shoes
        $names = array_map(fn($p) => $p->getName(), $results);
        $this->assertContains('Red Shirt', $names);
        $this->assertContains('Red Shoes', $names);
    }

    public function testUpdateProductReturnsNullIfNotFound(): void
    {
        $result = $this->service->updateProduct(999, ['price' => 200]);
        $this->assertNull($result);
    }

    public function testUpdateProductUpdatesProvidedFields(): void
    {
        $product = $this->service->createProduct(1, 'Old Name', 50, 'cat');
        $updated = $this->service->updateProduct($product->getId(), [
            'sale_price' => 75.5,
            'stock' => 100,
            'description' => 'Updated desc',
            'status' => 'active',
        ]);

        $this->assertEquals(75.5, $updated->getSalePrice());
        $this->assertEquals(100, $updated->getStock());
        $this->assertEquals('Updated desc', $updated->getDescription());
        $this->assertEquals('active', $updated->getStatus());
    }

    public function testActivateProductSetsActive(): void
    {
        $product = $this->service->createProduct(1, 'P', 10, 'cat');
        $product->deactivate();
        $this->assertEquals('inactive', $product->getStatus());

        $reactivated = $this->service->activateProduct($product->getId());
        $this->assertEquals('active', $reactivated->getStatus());
    }

    public function testDeactivateProductSetsInactive(): void
    {
        $product = $this->service->createProduct(1, 'P', 10, 'cat');
        $activated = $this->service->activateProduct($product->getId());
        $this->assertEquals('active', $activated->getStatus());

        $deactivated = $this->service->deactivateProduct($product->getId());
        $this->assertEquals('inactive', $deactivated->getStatus());
    }

    public function testUpdateStockChangesQuantity(): void
    {
        $product = $this->service->createProduct(1, 'P', 10, 'cat');
        $this->service->updateStock($product->getId(), 50);
        $this->assertEquals(50, $product->getStock());
    }

    public function testGetActiveProductsFiltersByStatus(): void
    {
        $this->service->createProduct(1, 'Active1', 10, 'cat')->activate();
        $this->service->createProduct(1, 'Active2', 20, 'cat')->activate();
        $this->service->createProduct(1, 'Inactive1', 30, 'cat')->deactivate();

        $active = $this->service->getActiveProducts();
        $this->assertCount(2, $active);
        $names = array_map(fn($p) => $p->getName(), $active);
        $this->assertContains('Active1', $names);
        $this->assertContains('Active2', $names);
    }

    public function testCountActiveProductsReturnsCorrectCount(): void
    {
        $this->service->createProduct(1, 'P1', 10, 'cat')->activate();
        $this->service->createProduct(1, 'P2', 20, 'cat')->activate();
        $this->service->createProduct(1, 'P3', 30, 'cat')->deactivate();

        $this->assertEquals(2, $this->service->countActiveProducts());
    }

    public function testDeleteProductRemovesFromList(): void
    {
        $product = $this->service->createProduct(1, 'ToDelete', 10, 'cat');
        $id = $product->getId();
        $this->assertTrue($this->service->getProduct($id) !== null);

        $result = $this->service->deleteProduct($id);
        $this->assertTrue($result);
        $this->assertNull($this->service->getProduct($id));
    }

    public function testDeleteProductReturnsFalseIfNotFound(): void
    {
        $result = $this->service->deleteProduct(999);
        $this->assertFalse($result);
    }
}
