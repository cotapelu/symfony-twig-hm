<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceProduct;
use App\Ecommerce\Service\EcommerceService;
use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceIntegrationTest extends DatabaseIntegrationTestCase
{
    private EcommerceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = self::getContainer()->get(EcommerceService::class);
    }

    public function testProductPersistence(): void
    {
        // Create product with full data
        $product = $this->service->createProduct([
            'seller_id' => 'user-123',
            'name' => 'iPhone 15',
            'price' => 25000000,
            'category' => 'electronics',
            'description' => 'Latest iPhone model',
            'stock' => 100,
            'status' => 'active',
        ]);

        $this->assertNotNull($product->getId());

        // Retrieve from DB
        $retrieved = $this->service->getProduct($product->getId());
        $this->assertInstanceOf(EcommerceProduct::class, $retrieved);
        $this->assertSame('iPhone 15', $retrieved->getName());
        $this->assertSame(25000000, $retrieved->getPrice());
        $this->assertSame('electronics', $retrieved->getCategory());
        $this->assertSame(100, $retrieved->getStock());
        $this->assertTrue($retrieved->isActive());
    }

    public function testUpdateProduct(): void
    {
        $product = $this->service->createProduct([
            'seller_id' => 'user-456',
            'name' => 'Old Name',
            'price' => 100000,
            'category' => 'books',
        ]);

        $this->service->updateProduct($product->getId(), [
            'name' => 'New Name',
            'price' => 150000,
            'description' => 'Updated description',
            'stock' => 50,
        ]);

        $updated = $this->service->getProduct($product->getId());
        $this->assertSame('New Name', $updated->getName());
        $this->assertSame(150000, $updated->getPrice());
        $this->assertSame('Updated description', $updated->getDescription());
        $this->assertSame(50, $updated->getStock());
    }

    public function testDeleteProduct(): void
    {
        $product = $this->service->createProduct([
            'seller_id' => 'user-789',
            'name' => 'ToDelete',
            'price' => 5000,
            'category' => 'misc',
        ]);

        $id = $product->getId();
        $this->assertNotNull($this->service->getProduct($id));

        $result = $this->service->deleteProduct($id);
        $this->assertTrue($result);

        $this->assertNull($this->service->getProduct($id));
    }

    public function testGetProductsBySeller(): void
    {
        $this->service->createProduct(['seller_id' => 'U1', 'name' => 'P1', 'price' => 10, 'category' => 'cat']);
        $this->service->createProduct(['seller_id' => 'U1', 'name' => 'P2', 'price' => 20, 'category' => 'cat']);
        $this->service->createProduct(['seller_id' => 'U2', 'name' => 'P3', 'price' => 30, 'category' => 'cat']);

        $seller1 = $this->service->getSellerProducts('U1', 10);
        $this->assertCount(2, $seller1);
        $names = array_map(fn($p) => $p->getName(), $seller1);
        $this->assertContains('P1', $names);
        $this->assertContains('P2', $names);
    }

    public function testGetProductsByCategory(): void
    {
        $this->service->createProduct(['seller_id' => '1', 'name' => 'A', 'price' => 10, 'category' => 'electronics']);
        $this->service->createProduct(['seller_id' => '1', 'name' => 'B', 'price' => 20, 'category' => 'books']);
        $this->service->createProduct(['seller_id' => '1', 'name' => 'C', 'price' => 30, 'category' => 'electronics']);

        $electronics = $this->service->getProductsByCategory('electronics', 10);
        $this->assertCount(2, $electronics);
    }

    public function testSearchProducts(): void
    {
        $this->service->createProduct(['seller_id' => '1', 'name' => 'Red Shirt', 'price' => 50, 'category' => 'clothing', 'description' => 'A bright red shirt']);
        $this->service->createProduct(['seller_id' => '1', 'name' => 'Blue Jeans', 'price' => 100, 'category' => 'clothing', 'description' => 'Denim jeans']);
        $this->service->createProduct(['seller_id' => '1', 'name' => 'Red Shoes', 'price' => 80, 'category' => 'footwear', 'description' => 'Comfortable red shoes']);

        $results = $this->service->searchProducts('red', 10);
        $this->assertCount(2, $results);
        $names = array_map(fn($p) => $p->getName(), $results);
        $this->assertContains('Red Shirt', $names);
        $this->assertContains('Red Shoes', $names);
    }

    public function testGetFeaturedProductsSortedByPriceDesc(): void
    {
        $p1 = $this->service->createProduct(['seller_id' => '1', 'name' => 'Cheap', 'price' => 10, 'category' => 'cat']);
        $this->service->activateProduct($p1->getId());
        $p2 = $this->service->createProduct(['seller_id' => '1', 'name' => 'Expensive', 'price' => 100, 'category' => 'cat']);
        $this->service->activateProduct($p2->getId());

        $featured = $this->service->getFeaturedProducts(2);
        $this->assertCount(2, $featured);
        $this->assertSame('Expensive', $featured[0]->getName()); // higher first
        $this->assertSame('Cheap', $featured[1]->getName());
    }

    public function testCountActiveProducts(): void
    {
        $p1 = $this->service->createProduct(['seller_id' => '1', 'name' => 'A', 'price' => 10, 'category' => 'cat']);
        $this->service->activateProduct($p1->getId());
        $p2 = $this->service->createProduct(['seller_id' => '1', 'name' => 'B', 'price' => 20, 'category' => 'cat']);
        $this->service->activateProduct($p2->getId());
        $p3 = $this->service->createProduct(['seller_id' => '1', 'name' => 'C', 'price' => 30, 'category' => 'cat']);
        $this->service->deactivateProduct($p3->getId());

        $count = $this->service->countActiveProducts();
        $this->assertSame(2, $count);
    }

    public function testActivateAndDeactivate(): void
    {
        $product = $this->service->createProduct(['seller_id' => '1', 'name' => 'P', 'price' => 10, 'category' => 'cat']);
        $this->assertTrue($product->isActive()); // default active

        $deactivated = $this->service->deactivateProduct($product->getId());
        $this->assertFalse($deactivated->isActive());

        $reactivated = $this->service->activateProduct($deactivated->getId());
        $this->assertTrue($reactivated->isActive());
    }

    public function testUpdateStock(): void
    {
        $product = $this->service->createProduct([
            'seller_id' => '1',
            'name' => 'P',
            'price' => 10,
            'category' => 'cat',
            'stock' => 0,
        ]);

        $this->service->updateStock($product->getId(), 100);
        $updated = $this->service->getProduct($product->getId());
        $this->assertSame(100, $updated->getStock());
    }

    public function testGetProductsWithPagination(): void
    {
        // Create 5 products
        for ($i = 0; $i < 5; $i++) {
            $this->service->createProduct([
                'seller_id' => '1',
                'name' => "Product $i",
                'price' => 10 * $i,
                'category' => 'cat',
            ]);
        }

        $page = $this->service->getProducts(limit: 2, offset: 2);
        $this->assertCount(2, $page);
        $this->assertSame('Product 2', $page[0]->getName());
        $this->assertSame('Product 3', $page[1]->getName());
    }
}
