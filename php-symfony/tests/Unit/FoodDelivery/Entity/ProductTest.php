<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Product;
use App\FoodDelivery\Entity\Restaurant;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductTest extends TestCase
{
    private function createProduct(): Product
    {
        return new Product();
    }

    private function createRestaurant(): Restaurant
    {
        return new Restaurant();
    }

    public function testConstructorInitializesProperties(): void
    {
        $product = $this->createProduct();


        // Defaults:
        $this->assertNull($product->getDescription());
        $this->assertNull($product->getOriginalPrice());
        $this->assertNull($product->getImageUrl());
        $this->assertNull($product->getPreparationTime());
        $this->assertSame('active', $product->getStatus());
        $this->assertFalse($product->isPopular());
        $this->assertFalse($product->isNew());
        $this->assertInstanceOf(Collection::class, $product->getOrderItems());
        $this->assertCount(0, $product->getOrderItems());
        $this->assertInstanceOf(\DateTime::class, $product->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $product = $this->createProduct();

        $product->setId(10);
        $this->assertSame(10, $product->getId());

        $product->setName('Pizza');
        $this->assertSame('Pizza', $product->getName());

        $product->setDescription('Delicious');
        $this->assertSame('Delicious', $product->getDescription());

        $product->setPrice(12.99);
        $this->assertSame(12.99, $product->getPrice());

        $product->setOriginalPrice(15.99);
        $this->assertSame(15.99, $product->getOriginalPrice());

        $product->setCategory('Food');
        $this->assertSame('Food', $product->getCategory());

        $product->setImageUrl('http://example.com/pizza.jpg');
        $this->assertSame('http://example.com/pizza.jpg', $product->getImageUrl());

        $product->setStatus(Product::STATUS_OUT_OF_STOCK);
        $this->assertSame(Product::STATUS_OUT_OF_STOCK, $product->getStatus());

        $product->setPreparationTime(30);
        $this->assertSame(30, $product->getPreparationTime());

        $product->setPopular(true);
        $this->assertTrue($product->isPopular());

        $product->setNew(true);
        $this->assertTrue($product->isNew());

        $restaurant = $this->createRestaurant();
        $product->setRestaurant($restaurant);
        $this->assertSame($restaurant, $product->getRestaurant());
    }

    public function testGetDiscountPercentage(): void
    {
        $product = $this->createProduct();

        $product->setPrice(80.0);
        $product->setOriginalPrice(100.0);
        $this->assertSame(20, $product->getDiscountPercentage());

        $product->setOriginalPrice(null);
        $this->assertSame(0, $product->getDiscountPercentage());

        $product->setOriginalPrice(80.0);
        $product->setPrice(100.0);
        $this->assertSame(0, $product->getDiscountPercentage());
    }
}
