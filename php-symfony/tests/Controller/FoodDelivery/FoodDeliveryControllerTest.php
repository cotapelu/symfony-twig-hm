<?php

declare(strict_types=1);

namespace App\Tests\Controller\FoodDelivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Controller\FoodDeliveryController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class FoodDeliveryControllerTest extends TestCase
{
    private FoodDeliveryController $controller;
    private \PHPUnit\Framework\MockObject\MockObject $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(\App\FoodDelivery\Service\FoodDeliveryService::class);
        $this->controller = new FoodDeliveryController($this->service);
        // Set authenticated user for tests requiring auth
        $user = $this->createMock(\App\User\Entity\User::class);
        $user->method('getId')->willReturn('user123');
        $user->method('getUserIdentifier')->willReturn('user123');
        $this->controller->setTestUser($user);

        // All service method mocks are configured per-test to avoid interference.
    }

    public function testListRestaurantsSuccess(): void
    {
        // Mock restaurants data
        $restaurant1 = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant1->method('getId')->willReturn(1);
        $restaurant1->method('getName')->willReturn('Nhà hàng A');
        $restaurant1->method('getCuisine')->willReturn('Việt Nam');
        $restaurant1->method('getRating')->willReturn(4.5);
        $restaurant1->method('getDeliveryTime')->willReturn('30-45 min');
        $restaurant1->method('getMinOrder')->willReturn(50000.0);
        $restaurant1->method('getImage')->willReturn('/images/restaurant1.jpg');

        $restaurant2 = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant2->method('getId')->willReturn(2);
        $restaurant2->method('getName')->willReturn('Quán B');
        $restaurant2->method('getCuisine')->willReturn('Hàn Quốc');
        $restaurant2->method('getRating')->willReturn(4.2);
        $restaurant2->method('getDeliveryTime')->willReturn('25-35 min');
        $restaurant2->method('getMinOrder')->willReturn(100000.0);
        $restaurant2->method('getImage')->willReturn('/images/restaurant2.jpg');

        $this->service->expects($this->once())
            ->method('countActiveRestaurants')
            ->willReturn(2);
        $this->service->expects($this->once())
            ->method('getActiveRestaurants')
            ->willReturn([$restaurant1, $restaurant2]);

        $request = new Request();
        $result = $this->controller->listRestaurants($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThanOrEqual(2, count($data['data']));

        $restaurant1 = $data['data'][0];
        $this->assertEquals(1, $restaurant1['id']);
        $this->assertEquals('Nhà hàng A', $restaurant1['name']);
        $this->assertEquals('Việt Nam', $restaurant1['cuisine']);
        $this->assertEquals(4.5, $restaurant1['rating']);
        $this->assertEquals('30-45 min', $restaurant1['deliveryTime']);
        $this->assertEquals(50000, $restaurant1['minOrder']);
        $this->assertEquals('/images/restaurant1.jpg', $restaurant1['image']);

        $restaurant2 = $data['data'][1];
        $this->assertEquals(2, $restaurant2['id']);
        $this->assertEquals('Quán B', $restaurant2['name']);
        $this->assertEquals('Hàn Quốc', $restaurant2['cuisine']);
        $this->assertEquals(4.2, $restaurant2['rating']);
        $this->assertEquals('25-35 min', $restaurant2['deliveryTime']);
        $this->assertEquals(100000, $restaurant2['minOrder']);
    }

    public function testListRestaurantsReturnsCorrectStructure(): void
    {
        // Mock restaurants
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(1);
        $restaurant->method('getName')->willReturn('Nhà hàng A');
        $restaurant->method('getCuisine')->willReturn('Việt Nam');
        $restaurant->method('getRating')->willReturn(4.5);
        $restaurant->method('getDeliveryTime')->willReturn('30-45 min');
        $restaurant->method('getMinOrder')->willReturn(50000.0);
        $restaurant->method('getImage')->willReturn('/images/restaurant1.jpg');

        $this->service->expects($this->once())
            ->method('countActiveRestaurants')
            ->willReturn(1);
        $this->service->expects($this->once())
            ->method('getActiveRestaurants')
            ->willReturn([$restaurant]);

        $request = new Request();
        $result = $this->controller->listRestaurants($request);

        $data = json_decode($result->getContent(), true);

        foreach ($data['data'] as $restaurant) {
            $this->assertArrayHasKey('id', $restaurant);
            $this->assertArrayHasKey('name', $restaurant);
            $this->assertArrayHasKey('cuisine', $restaurant);
            $this->assertArrayHasKey('rating', $restaurant);
            $this->assertArrayHasKey('deliveryTime', $restaurant);
            $this->assertArrayHasKey('minOrder', $restaurant);
            $this->assertArrayHasKey('image', $restaurant);
            $this->assertIsInt($restaurant['id']);
            $this->assertIsString($restaurant['name']);
            $this->assertIsString($restaurant['cuisine']);
            $this->assertIsNumeric($restaurant['rating']);
            $this->assertIsString($restaurant['deliveryTime']);
            $this->assertIsInt($restaurant['minOrder']);
            $this->assertIsString($restaurant['image']);
        }
    }

    public function testGetRestaurantSuccess(): void
    {
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(1);
        $restaurant->method('getName')->willReturn('Nhà hàng 1');
        $restaurant->method('getCuisine')->willReturn('Việt Nam');
        $restaurant->method('getRating')->willReturn(4.5);
        $restaurant->method('getDeliveryTime')->willReturn('30-45 min');
        $restaurant->method('getMinOrder')->willReturn(50000.0);
        $restaurant->method('getImage')->willReturn('/images/restaurant1.jpg');
        $restaurant->method('getMenu')->willReturn([
            ['id' => 1, 'name' => 'Phở bò', 'price' => 45000, 'category' => 'Món chính'],
            ['id' => 2, 'name' => 'Bún chả', 'price' => 50000, 'category' => 'Món chính']
        ]);

        $this->service->expects($this->once())
            ->method('getRestaurant')
            ->with(1)
            ->willReturn($restaurant);

        $request = new Request();
        $result = $this->controller->getRestaurant($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('Nhà hàng 1', $data['name']);
    }

    public function testGetRestaurantWithDifferentId(): void
    {
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(999);
        $restaurant->method('getName')->willReturn('Nhà hàng 999');
        $restaurant->method('getCuisine')->willReturn('Việt Nam');
        $restaurant->method('getRating')->willReturn(4.0);
        $restaurant->method('getDeliveryTime')->willReturn('20-30 min');
        $restaurant->method('getMinOrder')->willReturn(50000.0);
        $restaurant->method('getImage')->willReturn('/images/restaurant999.jpg');
        $restaurant->method('getMenu')->willReturn([]);

        $this->service->expects($this->once())
            ->method('getRestaurant')
            ->with(999)
            ->willReturn($restaurant);

        $request = new Request();
        $result = $this->controller->getRestaurant($request, 999);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(999, $data['id']);
        $this->assertEquals('Nhà hàng 999', $data['name']);
    }

    public function testGetRestaurantReturnsMenu(): void
    {
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(1);
        $restaurant->method('getName')->willReturn('Nhà hàng A');
        $restaurant->method('getMenu')->willReturn([
            ['id' => 1, 'name' => 'Phở bò', 'price' => 45000, 'category' => 'Món chính'],
            ['id' => 2, 'name' => 'Bún chả', 'price' => 50000, 'category' => 'Món chính']
        ]);

        $this->service->expects($this->once())
            ->method('getRestaurant')
            ->with(1)
            ->willReturn($restaurant);

        $request = new Request();
        $result = $this->controller->getRestaurant($request, 1);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('menu', $data);
        $this->assertIsArray($data['menu']);
        $this->assertCount(2, $data['menu']);

        $menuItem1 = $data['menu'][0];
        $this->assertEquals(1, $menuItem1['id']);
        $this->assertEquals('Phở bò', $menuItem1['name']);
        $this->assertEquals(45000, $menuItem1['price']);
        $this->assertEquals('Món chính', $menuItem1['category']);

        $menuItem2 = $data['menu'][1];
        $this->assertEquals(2, $menuItem2['id']);
        $this->assertEquals('Bún chả', $menuItem2['name']);
        $this->assertEquals(50000, $menuItem2['price']);
        $this->assertEquals('Món chính', $menuItem2['category']);
    }

    public function testGetRestaurantMenuStructure(): void
    {
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getMenu')->willReturn([
            ['id' => 1, 'name' => 'Phở bò', 'price' => 45000, 'category' => 'Món chính'],
            ['id' => 2, 'name' => 'Bún chả', 'price' => 50000, 'category' => 'Món chính']
        ]);
        // Also need to return other properties perhaps not needed
        $restaurant->method('getId')->willReturn(1);
        $restaurant->method('getName')->willReturn('Test');
        $restaurant->method('getCuisine')->willReturn('Test');
        $restaurant->method('getRating')->willReturn(4.0);
        $restaurant->method('getDeliveryTime')->willReturn('30 min');
        $restaurant->method('getMinOrder')->willReturn(50000.0);
        $restaurant->method('getImage')->willReturn('/test.jpg');

        $this->service->expects($this->once())
            ->method('getRestaurant')
            ->with(1)
            ->willReturn($restaurant);

        $request = new Request();
        $result = $this->controller->getRestaurant($request, 1);

        $data = json_decode($result->getContent(), true);

        foreach ($data['menu'] as $menuItem) {
            $this->assertArrayHasKey('id', $menuItem);
            $this->assertArrayHasKey('name', $menuItem);
            $this->assertArrayHasKey('price', $menuItem);
            $this->assertArrayHasKey('category', $menuItem);
            $this->assertIsInt($menuItem['id']);
            $this->assertIsString($menuItem['name']);
            $this->assertIsInt($menuItem['price']);
            $this->assertIsString($menuItem['category']);
        }
    }

    public function testCreateOrderSuccess(): void
    {
        // Mock restaurant
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(1);

        // Mock order items
        $item1 = $this->createMock(\App\FoodDelivery\Entity\OrderItem::class);
        $product1 = $this->createMock(\App\FoodDelivery\Entity\Product::class);
        $product1->method('getId')->willReturn(1);
        $item1->method('getProduct')->willReturn($product1);
        $item1->method('getQuantity')->willReturn(2);
        $item1->method('getUnitPrice')->willReturn(70000);

        $item2 = $this->createMock(\App\FoodDelivery\Entity\OrderItem::class);
        $product2 = $this->createMock(\App\FoodDelivery\Entity\Product::class);
        $product2->method('getId')->willReturn(2);
        $item2->method('getProduct')->willReturn($product2);
        $item2->method('getQuantity')->willReturn(1);
        $item2->method('getUnitPrice')->willReturn(70000);

        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1234);
        $order->method('getOrderNumber')->willReturn('FD-2024-001');
        $order->method('getRestaurant')->willReturn($restaurant);
        $order->method('getItems')->willReturn([$item1, $item2]);
        $order->method('getTotal')->willReturn(140000);
        $order->method('getStatus')->willReturn('pending');
        $order->method('getCreatedAt')->willReturn(new \DateTime('2024-01-01 10:00:00'));

        $this->service->expects($this->once())
            ->method('createOrder')
            ->with($this->anything())
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'restaurantId' => 1,
                'items' => [
                    ['id' => 1, 'quantity' => 2],
                    ['id' => 2, 'quantity' => 1],
                ],
                'total' => 140000,
                'address' => '123 Test St',
            ])
        );

        $result = $this->controller->createOrder($request);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('restaurantId', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('createdAt', $data);

        $this->assertEquals(1, $data['restaurantId']);
        $this->assertEquals(140000, $data['total']);
        $this->assertEquals('pending', $data['status']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['createdAt']);
    }

    public function testCreateOrderWithoutRequiredFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'items' => [],
            ])
        );

        $result = $this->controller->createOrder($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Missing required field: restaurantId', $data['error']);
    }

    public function testCreateOrderInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->createOrder($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreateOrderIdIsBetween1000And9999(): void
    {
        // Mock order with ID in range
        $restaurant = $this->createMock(\App\FoodDelivery\Entity\Restaurant::class);
        $restaurant->method('getId')->willReturn(1);
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(5678); // in range
        $order->method('getOrderNumber')->willReturn('FD-TEST-5678');
        $order->method('getRestaurant')->willReturn($restaurant);
        $order->method('getItems')->willReturn([]);
        $order->method('getTotal')->willReturn(150000);
        $order->method('getStatus')->willReturn('pending');
        $order->method('getCreatedAt')->willReturn(new \DateTime());

        $this->service->expects($this->once())
            ->method('createOrder')
            ->willReturn($order);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'restaurantId' => 1,
                'items' => [['id' => 1, 'quantity' => 1]],
                'deliveryAddress' => '123 Test St',
                'lat' => 21.0,
                'lng' => 105.0,
                'total' => 150000
            ])
        );

        $result = $this->controller->createOrder($request);
        $data = json_decode($result->getContent(), true);
        $this->assertGreaterThanOrEqual(1000, $data['id']);
        $this->assertLessThanOrEqual(9999, $data['id']);
        $this->assertIsInt($data['id']);
    }

    public function testGetOrderSuccess(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1234);
        $order->method('getStatus')->willReturn('delivering');
        $date = new \DateTime('18:30');
        $order->method('getEstimatedDeliveryTime')->willReturn($date);
        $order->method('getCustomerId')->willReturn('user123'); // for ownership

        // Expect getOrder to be called (at least twice: once in controller, once in validateOwnership)
        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1234)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->getOrder($request, 1234);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1234, $data['id']);
        $this->assertEquals('delivering', $data['status']);
        $this->assertEquals('18:30', $data['estimatedDelivery']);
    }

    public function testGetOrderDifferentStatuses(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getStatus')->willReturn('delivering');
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $request = new Request();
        $result1 = $this->controller->getOrder($request, 100);
        $data1 = json_decode($result1->getContent(), true);
        $this->assertEquals('delivering', $data1['status']);

        $result2 = $this->controller->getOrder($request, 200);
        $data2 = json_decode($result2->getContent(), true);
        $this->assertEquals('delivering', $data2['status']);
    }

    public function testGetOrderReturnsCorrectStructure(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getStatus')->willReturn('pending');
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('12:00'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->getOrder($request, 1);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('estimatedDelivery', $data);
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['status']);
        $this->assertIsString($data['estimatedDelivery']);
    }

    public function testTrackOrderSuccess(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1234);
        $order->method('getStatus')->willReturn('on_the_way');
        $order->method('getDriverLocation')->willReturn(['lat' => 21.0, 'lng' => 105.0]);
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1234)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->trackOrder($request, 1234);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals(1234, $data['orderId']);
        $this->assertEquals('on_the_way', $data['status']);
    }

    public function testTrackOrderReturnsDriverLocation(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getStatus')->willReturn('delivering');
        $order->method('getDriverLocation')->willReturn(['lat' => 21.0285, 'lng' => 105.8542]);
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->trackOrder($request, 1);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('driverLocation', $data);
        $this->assertArrayHasKey('lat', $data['driverLocation']);
        $this->assertArrayHasKey('lng', $data['driverLocation']);
        $this->assertEquals(21.0285, $data['driverLocation']['lat']);
        $this->assertEquals(105.8542, $data['driverLocation']['lng']);
    }

    public function testTrackOrderReturnsEstimatedArrival(): void
    {
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getStatus')->willReturn('delivering');
        $order->method('getDriverLocation')->willReturn(['lat' => 21.0, 'lng' => 105.0]);
        $order->method('getEstimatedDeliveryTime')->willReturn(15); // minutes
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->trackOrder($request, 1);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('estimatedArrival', $data);
        $this->assertEquals('15 phút', $data['estimatedArrival']);
    }

    public function testTrackOrderReturnsCorrectStructure(): void
    {
        // Mock order for structure test
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getStatus')->willReturn('delivering');
        $order->method('getDriverLocation')->willReturn(['lat'=>21.0, 'lng'=>105.0]);
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->trackOrder($request, 1);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('orderId', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('driverLocation', $data);
        $this->assertArrayHasKey('estimatedArrival', $data);

        $this->assertIsInt($data['orderId']);
        $this->assertIsString($data['status']);
        $this->assertIsArray($data['driverLocation']);
        $this->assertIsString($data['estimatedArrival']);
    }

    public function testTrackOrderWithDifferentOrderIds(): void
    {
        // Mock order for different ID test (dynamic)
        $this->service->method('getOrder')->willReturnCallback(function($id) {
            $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
            $order->method('getId')->willReturn($id);
            $order->method('getStatus')->willReturn('delivering');
            $order->method('getDriverLocation')->willReturn(['lat'=>21.0, 'lng'=>105.0]);
            $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
            $order->method('getCustomerId')->willReturn('user123');
            return $order;
        });
        $request = new Request();

        $result1 = $this->controller->trackOrder($request, 100);
        $data1 = json_decode($result1->getContent(), true);
        $this->assertEquals(100, $data1['orderId']);

        $result2 = $this->controller->trackOrder($request, 200);
        $data2 = json_decode($result2->getContent(), true);
        $this->assertEquals(200, $data2['orderId']);
    }

    public function testTrackOrderDriverLocationCoordinates(): void
    {
        // Mock order for driver location test
        $order = $this->createMock(\App\FoodDelivery\Entity\Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getStatus')->willReturn('delivering');
        $order->method('getDriverLocation')->willReturn(['lat'=>21.0285, 'lng'=>105.8542]);
        $order->method('getEstimatedDeliveryTime')->willReturn(new \DateTime('18:30'));
        $order->method('getCustomerId')->willReturn('user123');

        $this->service->expects($this->atLeastOnce())
            ->method('getOrder')
            ->with(1)
            ->willReturn($order);

        $request = new Request();
        $result = $this->controller->trackOrder($request, 1);

        $data = json_decode($result->getContent(), true);

        $lat = $data['driverLocation']['lat'];
        $lng = $data['driverLocation']['lng'];

        $this->assertIsFloat($lat);
        $this->assertIsFloat($lng);
        $this->assertGreaterThanOrEqual(-90, $lat);
        $this->assertLessThanOrEqual(90, $lat);
        $this->assertGreaterThanOrEqual(-180, $lng);
        $this->assertLessThanOrEqual(180, $lng);
    }
}
