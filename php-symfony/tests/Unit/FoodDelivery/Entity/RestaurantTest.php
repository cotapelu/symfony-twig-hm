<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Restaurant;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RestaurantTest extends TestCase
{
    private function createRestaurant(): Restaurant
    {
        return new Restaurant();
    }

    public function testConstructorInitializesCreatedAt(): void
    {
        $r = $this->createRestaurant();
        $this->assertInstanceOf(\DateTime::class, $r->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $r = $this->createRestaurant();
        $this->assertSame(Restaurant::STATUS_PENDING, $r->getStatus());
        $this->assertSame(0.0, $r->getRating());
        $this->assertSame(0, $r->getTotalReviews());
        $this->assertSame(0.0, $r->getDeliveryFee());
        $this->assertSame(30, $r->getDeliveryTime());
        $this->assertSame(0.0, $r->getMinOrder());
        $this->assertNull($r->getDescription());
        $this->assertNull($r->getCuisine());
        $this->assertNull($r->getImageUrl());
        $this->assertNull($r->getPhone());
        // Collections should be initialized (empty)
        $this->assertInstanceOf(Collection::class, $r->getProducts());
        $this->assertInstanceOf(Collection::class, $r->getOrders());
        $this->assertCount(0, $r->getProducts());
        $this->assertCount(0, $r->getOrders());
    }

    public function testGettersAndSetters(): void
    {
        $r = $this->createRestaurant();

        $r->setOwnerId('owner-123');
        $this->assertSame('owner-123', $r->getOwnerId());

        $r->setName('Burger Place');
        $this->assertSame('Burger Place', $r->getName());

        $r->setAddress('123 Food St');
        $this->assertSame('123 Food St', $r->getAddress());

        $r->setLatitude(52.5200);
        $this->assertSame(52.5200, $r->getLatitude());

        $r->setLongitude(13.4050);
        $this->assertSame(13.4050, $r->getLongitude());

        $r->setDescription('Best burgers in town');
        $this->assertSame('Best burgers in town', $r->getDescription());

        $r->setStatus(Restaurant::STATUS_ACTIVE);
        $this->assertSame(Restaurant::STATUS_ACTIVE, $r->getStatus());

        $r->setRating(4.5);
        $this->assertSame(4.5, $r->getRating());

        $r->setTotalReviews(150);
        $this->assertSame(150, $r->getTotalReviews());

        $r->setDeliveryFee(2.99);
        $this->assertSame(2.99, $r->getDeliveryFee());

        $r->setDeliveryTime(45);
        $this->assertSame(45, $r->getDeliveryTime());

        $r->setMinOrder(15.0);
        $this->assertSame(15.0, $r->getMinOrder());

        $r->setCuisine('American');
        $this->assertSame('American', $r->getCuisine());

        $r->setImageUrl('http://example.com/image.jpg');
        $this->assertSame('http://example.com/image.jpg', $r->getImageUrl());

        $r->setPhone('+1 555 1234');
        $this->assertSame('+1 555 1234', $r->getPhone());
    }
}
