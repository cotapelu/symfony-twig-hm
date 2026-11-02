<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Restaurant;
use App\FoodDelivery\Entity\Product;
use App\FoodDelivery\Repository\RestaurantRepository;
use App\FoodDelivery\Repository\ProductRepository;
use App\FoodDelivery\Service\RestaurantService;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class RestaurantServiceTest extends TestCase
{
    private RestaurantRepository $restaurantRepoMock;
    private ProductRepository $productRepoMock;
    private RestaurantService $service;

    protected function setUp(): void
    {
        $this->restaurantRepoMock = $this->createMock(RestaurantRepository::class);
        $this->productRepoMock = $this->createMock(ProductRepository::class);
        $this->service = new RestaurantService($this->restaurantRepoMock, $this->productRepoMock);
    }

    public function testCreateRestaurantSuccess(): void
    {
        $data = [
            'ownerId' => 'owner1',
            'name' => 'Test Restaurant',
            'description' => 'A test place',
            'address' => '123 Main St',
            'latitude' => 10.0,
            'longitude' => 20.0,
            'cuisine' => 'Italian',
            'phone' => '555-1234',
            'imageUrl' => 'http://example.com/image.jpg',
            'deliveryFee' => 5.0,
            'deliveryTime' => 30,
        ];

        $this->restaurantRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Restaurant::class));

        $result = $this->service->createRestaurant($data);

        $this->assertInstanceOf(Restaurant::class, $result);
        $this->assertSame('owner1', $result->getOwnerId());
        $this->assertSame('Test Restaurant', $result->getName());
        $this->assertSame('A test place', $result->getDescription());
        $this->assertSame('123 Main St', $result->getAddress());
        $this->assertEquals(10.0, $result->getLatitude());
        $this->assertEquals(20.0, $result->getLongitude());
        $this->assertSame('Italian', $result->getCuisine());
        $this->assertSame('555-1234', $result->getPhone());
        $this->assertSame('http://example.com/image.jpg', $result->getImageUrl());
        $this->assertEquals(5.0, $result->getDeliveryFee());
        $this->assertSame(30, $result->getDeliveryTime());
        $this->assertSame(Restaurant::STATUS_PENDING, $result->getStatus());
    }

    public function testUpdateRestaurantReturnsNullIfNotFound(): void
    {
        $this->restaurantRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);

        $result = $this->service->updateRestaurant(999, ['name' => 'New Name']);

        $this->assertNull($result);
    }

    public function testUpdateRestaurantPartialUpdate(): void
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Old Name');
        $restaurant->setDescription('Old desc');
        $restaurant->setAddress('Old addr');
        $restaurant->setLatitude(1.0);
        $restaurant->setLongitude(2.0);
        $restaurant->setCuisine('Old cuisine');
        $restaurant->setPhone('oldphone');
        $restaurant->setImageUrl('old.jpg');
        $restaurant->setDeliveryFee(0.0);
        $restaurant->setDeliveryTime(20);
        $restaurant->setId(5);

        $this->restaurantRepoMock->expects($this->once())->method('find')->with(5)->willReturn($restaurant);
        $this->restaurantRepoMock->expects($this->once())->method('save')->with($restaurant);

        $result = $this->service->updateRestaurant(5, [
            'name' => 'New Name',
            'deliveryFee' => 10.0,
        ]);

        $this->assertSame($restaurant, $result);
        $this->assertSame('New Name', $result->getName());
        $this->assertSame('Old desc', $result->getDescription()); // unchanged
        $this->assertEquals(10.0, $result->getDeliveryFee());
        $this->assertSame(20, $result->getDeliveryTime()); // unchanged
    }

    public function testApproveRestaurantSuccess(): void
    {
        $restaurant = new Restaurant();
        $restaurant->setStatus(Restaurant::STATUS_PENDING);
        $restaurant->setId(5);

        $this->restaurantRepoMock->expects($this->once())->method('find')->with(5)->willReturn($restaurant);
        $this->restaurantRepoMock->expects($this->once())->method('save')->with($restaurant);

        $result = $this->service->approveRestaurant(5);

        $this->assertSame($restaurant, $result);
        $this->assertSame(Restaurant::STATUS_ACTIVE, $result->getStatus());
    }

    public function testApproveRestaurantReturnsNullIfNotFound(): void
    {
        $this->restaurantRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);

        $result = $this->service->approveRestaurant(999);

        $this->assertNull($result);
    }

    public function testSuspendRestaurantSuccess(): void
    {
        $restaurant = new Restaurant();
        $restaurant->setStatus(Restaurant::STATUS_ACTIVE);
        $restaurant->setId(5);

        $this->restaurantRepoMock->expects($this->once())->method('find')->with(5)->willReturn($restaurant);
        $this->restaurantRepoMock->expects($this->once())->method('save')->with($restaurant);

        $result = $this->service->suspendRestaurant(5);

        $this->assertSame($restaurant, $result);
        $this->assertSame(Restaurant::STATUS_SUSPENDED, $result->getStatus());
    }

    public function testSuspendRestaurantReturnsNullIfNotFound(): void
    {
        $this->restaurantRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);

        $result = $this->service->suspendRestaurant(999);

        $this->assertNull($result);
    }

    public function testGetRestaurant(): void
    {
        $restaurant = new Restaurant();
        $this->restaurantRepoMock->expects($this->once())->method('find')->with(5)->willReturn($restaurant);

        $result = $this->service->getRestaurant(5);

        $this->assertSame($restaurant, $result);
    }

    public function testGetRestaurantsByOwner(): void
    {
        $restaurants = [new Restaurant(), new Restaurant()];
        $this->restaurantRepoMock->expects($this->once())->method('findByOwner')->with('owner1')->willReturn($restaurants);

        $result = $this->service->getRestaurantsByOwner('owner1');

        $this->assertSame($restaurants, $result);
    }

    public function testAddProduct(): void
    {
        $restaurant = new Restaurant();
        $restaurant->setId(1);

        $productData = [
            'name' => 'Pizza',
            'description' => 'Cheesy pizza',
            'price' => 12.50,
            'category' => 'Main',
            'imageUrl' => 'http://example.com/pizza.jpg',
            'preparationTime' => 15,
            'isPopular' => true,
            'isNew' => false,
        ];

        $this->restaurantRepoMock->expects($this->once())->method('find')->with(1)->willReturn($restaurant);
        $this->productRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Product::class));

        $result = $this->service->addProduct(1, $productData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertSame('Pizza', $result->getName());
        $this->assertSame('Cheesy pizza', $result->getDescription());
        $this->assertEquals(12.50, $result->getPrice());
        $this->assertSame('Main', $result->getCategory());
        $this->assertSame('http://example.com/pizza.jpg', $result->getImageUrl());
        $this->assertSame(15, $result->getPreparationTime());
        $this->assertTrue($result->isPopular());
        $this->assertFalse($result->isNew());
        $this->assertSame($restaurant, $result->getRestaurant());
    }

    public function testGetProducts(): void
    {
        $products = [new Product(), new Product()];
        $this->productRepoMock->expects($this->once())->method('findByRestaurant')->with(1)->willReturn($products);

        $result = $this->service->getProducts(1);

        $this->assertSame($products, $result);
    }

    public function testGetPopularProducts(): void
    {
        $products = [new Product()];
        $this->productRepoMock->expects($this->once())->method('findPopularByRestaurant')->with(2)->willReturn($products);

        $result = $this->service->getPopularProducts(2);

        $this->assertSame($products, $result);
    }

    public function testGetProductCategories(): void
    {
        $categories = ['Pizza', 'Pasta'];
        $this->productRepoMock->expects($this->once())->method('getCategoriesByRestaurant')->with(1)->willReturn($categories);

        $result = $this->service->getProductCategories(1);

        $this->assertSame($categories, $result);
    }

    // updateProductRating could be tested but requires more setup; optional.
}
