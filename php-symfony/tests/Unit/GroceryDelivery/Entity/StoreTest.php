<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\Store;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StoreTest extends TestCase
{
    public function testConstructorSetsCreatedAt(): void
    {
        $store = new Store();
        $this->assertInstanceOf(\DateTime::class, $store->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $store = new Store();
        $store->setOwnerId('owner-123');
        $this->assertSame('owner-123', $store->getOwnerId());

        $store->setName('My Grocery Store');
        $this->assertSame('My Grocery Store', $store->getName());

        $store->setDescription('A nice store');
        $this->assertSame('A nice store', $store->getDescription());

        $store->setAddress('123 Main St');
        $this->assertSame('123 Main St', $store->getAddress());

        $store->setLatitude(10.123456);
        $this->assertSame(10.123456, $store->getLatitude());

        $store->setLongitude(20.654321);
        $this->assertSame(20.654321, $store->getLongitude());

        $store->setStatus(Store::STATUS_INACTIVE);
        $this->assertSame(Store::STATUS_INACTIVE, $store->getStatus());

        $store->setCategory('Food');
        $this->assertSame('Food', $store->getCategory());

        $store->setImageUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $store->getImageUrl());

        $store->setPhone('+1234567890');
        $this->assertSame('+1234567890', $store->getPhone());

        $store->setMinOrderAmount(50.00);
        $this->assertSame(50.00, $store->getMinOrderAmount());

        $store->setDeliveryFee(5.00);
        $this->assertSame(5.00, $store->getDeliveryFee());

        $store->setDeliveryTime(45);
        $this->assertSame(45, $store->getDeliveryTime());

        $store->setRating(4.5);
        $this->assertSame(4.5, $store->getRating());

        $store->setTotalReviews(100);
        $this->assertSame(100, $store->getTotalReviews());
    }

    public function testIdSetAndGet(): void
    {
        $store = new Store();
        $ref = new \ReflectionProperty($store, 'id');
        $ref->setAccessible(true);
        $ref->setValue($store, 7);
        $this->assertSame(7, $store->getId());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('active', Store::STATUS_ACTIVE);
        $this->assertSame('inactive', Store::STATUS_INACTIVE);
        $this->assertSame('suspended', Store::STATUS_SUSPENDED);
    }
}
