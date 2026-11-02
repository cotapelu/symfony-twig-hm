<?php declare(strict_types=1);

namespace Tests\Unit\FoodDelivery\Controller;

use App\FoodDelivery\Controller\FoodDeliveryController;
use App\FoodDelivery\Service\FoodDeliveryService;
use App\FoodDelivery\Entity\Restaurant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FoodDeliveryControllerTest extends TestCase
{
    private FoodDeliveryService&MockObject $serviceMock;
    private FoodDeliveryController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(FoodDeliveryService::class);
        $this->controller = new FoodDeliveryController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createRestaurantMock(
        int $id,
        string $name,
        ?string $cuisine = null,
        float $rating = 0.0,
        int $deliveryTime = 30,
        float $minOrder = 0.0,
        ?string $imageUrl = null
    ): Restaurant {
        $restaurant = $this->createMock(Restaurant::class);
        $restaurant->method('getId')->willReturn($id);
        $restaurant->method('getName')->willReturn($name);
        $restaurant->method('getCuisine')->willReturn($cuisine);
        $restaurant->method('getRating')->willReturn($rating);
        $restaurant->method('getDeliveryTime')->willReturn($deliveryTime);
        $restaurant->method('getMinOrder')->willReturn($minOrder);
        $restaurant->method('getImage')->willReturn($imageUrl);
        $restaurant->method('getMenu')->willReturn([]);
        return $restaurant;
    }

    // ==================== listRestaurants ====================

    public function testListRestaurants_NoFilters_ReturnsSuccess(): void
    {
        $page = 1; $limit = 10; $offset = 0; $total = 2;
        $restaurant1 = $this->createRestaurantMock(1, 'Pizza Place', 'Italian', 4.5, 45, 50000.0, 'pizza.jpg');
        $restaurant2 = $this->createRestaurantMock(2, 'Burger Joint', 'American', 4.2, 30, 30000.0, 'burger.jpg');

        $this->serviceMock->expects($this->once())
            ->method('countActiveRestaurants')
            ->willReturn($total);
        $this->serviceMock->expects($this->once())
            ->method('getActiveRestaurants')
            ->with($limit, $offset)
            ->willReturn([$restaurant1, $restaurant2]);

        $request = Request::create('/api/food/restaurants', 'GET', ['page'=>$page, 'limit'=>$limit]);
        $response = $this->controller->listRestaurants($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
        $this->assertSame('Pizza Place', $data['data'][0]['name']);
        $this->assertSame(4.5, $data['data'][0]['rating']);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertSame($total, $data['pagination']['total']);
        $this->assertSame(1, $data['pagination']['page']);
        $this->assertSame($limit, $data['pagination']['limit']);
        $this->assertSame(1, $data['pagination']['totalPages']);
    }

    public function testListRestaurants_WithCuisine_ReturnsSuccess(): void
    {
        $cuisine = 'Italian'; $page = 1; $limit = 5; $offset = 0; $total = 1;
        $restaurant = $this->createRestaurantMock(3, 'Pasta House', 'Italian', 4.8, 40, 60000.0, 'pasta.jpg');

        $this->serviceMock->expects($this->once())
            ->method('countRestaurantsByCuisine')
            ->with($cuisine)
            ->willReturn($total);
        $this->serviceMock->expects($this->once())
            ->method('getRestaurantsByCuisine')
            ->with($cuisine, $limit, $offset)
            ->willReturn([$restaurant]);

        $request = Request::create('/api/food/restaurants', 'GET', ['cuisine'=>$cuisine, 'page'=>$page, 'limit'=>$limit]);
        $response = $this->controller->listRestaurants($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Pasta House', $data['data'][0]['name']);
        $this->assertSame('Italian', $data['data'][0]['cuisine']);
    }

    public function testListRestaurants_WithLocation_ReturnsSuccess(): void
    {
        $latitude = 10.0; $longitude = 106.0; $radius = 5.0; $page = 1; $limit = 20; $offset = 0; $total = 3;
        $r1 = $this->createRestaurantMock(4, 'Nearby Spot', 'Vietnamese', 4.0, 35, 40000.0);
        $r2 = $this->createRestaurantMock(5, 'Another Nearby', 'Chinese', 3.8, 40, 35000.0);

        $this->serviceMock->expects($this->once())
            ->method('countNearbyRestaurants')
            ->with($latitude, $longitude, $radius)
            ->willReturn($total);
        $this->serviceMock->expects($this->once())
            ->method('getNearbyRestaurants')
            ->with($latitude, $longitude, $radius, $limit, $offset)
            ->willReturn([$r1, $r2]);

        $request = Request::create('/api/food/restaurants', 'GET', [
            'latitude' => (string)$latitude,
            'longitude' => (string)$longitude,
            'radius' => $radius,
            'page' => $page,
            'limit' => $limit
        ]);
        $response = $this->controller->listRestaurants($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['data']);
        $this->assertSame(3, $data['pagination']['total']);
    }

    // ==================== getRestaurant ====================

    public function testGetRestaurant_Success(): void
    {
        $restaurantId = 10;
        $restaurant = $this->createRestaurantMock($restaurantId, 'Test Restaurant', 'French', 4.9, 50, 100000.0, 'test.jpg');

        $this->serviceMock->expects($this->once())
            ->method('getRestaurant')
            ->with($restaurantId)
            ->willReturn($restaurant);

        $request = new Request();
        $response = $this->controller->getRestaurant($request, $restaurantId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($restaurantId, $data['id']);
        $this->assertSame('Test Restaurant', $data['name']);
        $this->assertSame('French', $data['cuisine']);
        $this->assertSame(4.9, $data['rating']);
        $this->assertSame(50, $data['deliveryTime']);
        $this->assertSame(100000, $data['minOrder']);
        $this->assertSame([], $data['menu']);
    }

    public function testGetRestaurant_NotFound_Returns404(): void
    {
        $restaurantId = 999;
        $this->serviceMock->expects($this->once())
            ->method('getRestaurant')
            ->with($restaurantId)
            ->willReturn(null);

        $request = new Request();
        $response = $this->controller->getRestaurant($request, $restaurantId);

        $this->assertSame(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Restaurant not found', $data['error']);
    }
}
