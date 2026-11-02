<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Entity\Restaurant;
use App\FoodDelivery\Repository\OrderRepository;
use App\FoodDelivery\Repository\RestaurantRepository;
use App\FoodDelivery\Repository\ProductRepository;
use App\FoodDelivery\Repository\PaymentRepository;
use App\FoodDelivery\Entity\Product;
use App\FoodDelivery\Entity\Payment;
use App\FoodDelivery\Service\FoodDeliveryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FoodDeliveryServiceTest extends TestCase
{
    private OrderRepository&MockObject $orderRepo;
    private RestaurantRepository&MockObject $restaurantRepo;
    private ProductRepository&MockObject $productRepo;
    private PaymentRepository&MockObject $paymentRepo;
    private FoodDeliveryService $service;

    protected function setUp(): void
    {
        $this->orderRepo = $this->createMock(OrderRepository::class);
        $this->restaurantRepo = $this->createMock(RestaurantRepository::class);
        $this->productRepo = $this->createMock(ProductRepository::class);
        $this->paymentRepo = $this->createMock(PaymentRepository::class);

        $this->service = new FoodDeliveryService(
            $this->orderRepo,
            $this->restaurantRepo,
            $this->productRepo,
            $this->paymentRepo
        );
    }

    public function testGetOrder(): void
    {
        $orderId = 5;
        $order = $this->createMock(Order::class);
        $this->orderRepo->expects($this->once())->method('find')->with($orderId)->willReturn($order);
        $result = $this->service->getOrder($orderId);
        $this->assertSame($order, $result);
    }

    public function testGetCustomerOrders(): void
    {
        $customerId = 'customer-123';
        $orders = [new Order(), new Order()];
        $this->orderRepo->expects($this->once())->method('findByCustomer')->with($customerId, 50, 0)->willReturn($orders);
        $result = $this->service->getCustomerOrders($customerId);
        $this->assertSame($orders, $result);
    }

    public function testGetCustomerOrdersCount(): void
    {
        $customerId = 'customer-123';
        $count = 5;
        $this->orderRepo->expects($this->once())->method('countByCustomer')->with($customerId)->willReturn($count);
        $result = $this->service->getCustomerOrdersCount($customerId);
        $this->assertSame($count, $result);
    }

    public function testGetRestaurantOrders(): void
    {
        $restaurantId = 10;
        $orders = [new Order()];
        $this->orderRepo->expects($this->once())->method('findByRestaurant')->with($restaurantId)->willReturn($orders);
        $result = $this->service->getRestaurantOrders($restaurantId);
        $this->assertSame($orders, $result);
    }

    public function testGetActiveRestaurantOrders(): void
    {
        $restaurantId = 10;
        $orders = [new Order()];
        $this->orderRepo->expects($this->once())->method('findActiveOrdersByRestaurant')->with($restaurantId)->willReturn($orders);
        $result = $this->service->getActiveRestaurantOrders($restaurantId);
        $this->assertSame($orders, $result);
    }

    public function testGetPendingDeliveryOrders(): void
    {
        $orders = [new Order(), new Order()];
        $this->orderRepo->expects($this->once())->method('findPendingDeliveryOrders')->willReturn($orders);
        $result = $this->service->getPendingDeliveryOrders();
        $this->assertSame($orders, $result);
    }

    public function testGetActiveRestaurants(): void
    {
        $restaurants = [new Restaurant(), new Restaurant()];
        $this->restaurantRepo->expects($this->once())->method('findActiveRestaurants')->with(50, 0)->willReturn($restaurants);
        $result = $this->service->getActiveRestaurants();
        $this->assertSame($restaurants, $result);
    }

    public function testCountActiveRestaurants(): void
    {
        $count = 12;
        $this->restaurantRepo->expects($this->once())->method('countActiveRestaurants')->willReturn($count);
        $result = $this->service->countActiveRestaurants();
        $this->assertSame($count, $result);
    }

    public function testGetRestaurant(): void
    {
        $id = 5;
        $restaurant = $this->createMock(Restaurant::class);
        $this->restaurantRepo->expects($this->once())->method('find')->with($id)->willReturn($restaurant);
        $result = $this->service->getRestaurant($id);
        $this->assertSame($restaurant, $result);
    }

    public function testGetNearbyRestaurants(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $limit = 10;
        $offset = 0;
        $restaurants = [new Restaurant()];
        $this->restaurantRepo->expects($this->once())
            ->method('findNearbyRestaurants')
            ->with($lat, $lng, $radius, $limit, $offset)
            ->willReturn($restaurants);
        $result = $this->service->getNearbyRestaurants($lat, $lng, $radius, $limit, $offset);
        $this->assertSame($restaurants, $result);
    }

    public function testCountNearbyRestaurants(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $count = 3;
        $this->restaurantRepo->expects($this->once())
            ->method('countNearbyRestaurants')
            ->with($lat, $lng, $radius)
            ->willReturn($count);
        $result = $this->service->countNearbyRestaurants($lat, $lng, $radius);
        $this->assertSame($count, $result);
    }

    public function testSearchRestaurants(): void
    {
        $keyword = 'pizza';
        $limit = 20;
        $offset = 0;
        $restaurants = [new Restaurant()];
        $this->restaurantRepo->expects($this->once())
            ->method('searchRestaurants')
            ->with($keyword, $limit, $offset)
            ->willReturn($restaurants);
        $result = $this->service->searchRestaurants($keyword, $limit, $offset);
        $this->assertSame($restaurants, $result);
    }

    public function testCountSearchRestaurants(): void
    {
        $keyword = 'burger';
        $count = 7;
        $this->restaurantRepo->expects($this->once())
            ->method('countSearchRestaurants')
            ->with($keyword)
            ->willReturn($count);
        $result = $this->service->countSearchRestaurants($keyword);
        $this->assertSame($count, $result);
    }


    // ========== createOrder tests ==========
    public function testCreateOrderSuccess(): void
    {
        $data = [
            'customerId' => 'cust123',
            'restaurantId' => 1,
            'items' => [
                ['productId' => 101, 'quantity' => 2],
                ['productId' => 102, 'quantity' => 1],
            ],
            'deliveryAddress' => '123 Main St',
            'deliveryLatitude' => 10.0,
            'deliveryLongitude' => 20.0,
            'customerPhone' => '0123456789',
            'customerName' => 'John Doe',
            'deliveryInstructions' => 'Ring doorbell',
        ];

        $restaurant = $this->createMock(Restaurant::class);
        $restaurant->method('isActive')->willReturn(true);
        $restaurant->method('getDeliveryFee')->willReturn(5.00);
        $restaurant->method('getDeliveryTime')->willReturn(30);
        $this->restaurantRepo->expects($this->once())->method('find')->with(1)->willReturn($restaurant);

        $prod1 = $this->createMock(Product::class);
        $prod1->method('getPrice')->willReturn(15.0);
        $prod2 = $this->createMock(Product::class);
        $prod2->method('getPrice')->willReturn(20.0);
        $this->productRepo->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [101, $prod1],
                [102, $prod2],
            ]);

        $this->orderRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Order::class));

        $order = $this->service->createOrder($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($restaurant, $order->getRestaurant());
        $this->assertSame('cust123', $order->getCustomerId());
        $this->assertSame(Order::STATUS_PENDING, $order->getStatus());
        $this->assertSame(50.0, $order->getSubtotal());
        $this->assertSame(5.00, $order->getDeliveryFee());
        $this->assertSame(1.0, $order->getServiceFee());
        $this->assertSame(4.0, $order->getTax());
        $this->assertSame(0.0, $order->getDiscount());
        $this->assertSame(60.0, $order->getTotal());
        $this->assertSame('123 Main St', $order->getDeliveryAddress());
        $this->assertSame(10.0, $order->getDeliveryLatitude());
        $this->assertSame(20.0, $order->getDeliveryLongitude());
        $this->assertSame('Ring doorbell', $order->getDeliveryInstructions());
        $this->assertCount(2, $order->getItems());
    }

    public function testCreateOrder_RestaurantInactive_Throws(): void
    {
        $restaurant = $this->createMock(Restaurant::class);
        $restaurant->method('isActive')->willReturn(false);
        $this->restaurantRepo->expects($this->once())->method('find')->with(1)->willReturn($restaurant);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Restaurant not found or inactive');

        $this->service->createOrder([
            'customerId' => 'c1',
            'restaurantId' => 1,
            'items' => [['productId' => 101, 'quantity' => 1]],
            'deliveryAddress' => 'addr',
            'deliveryLatitude' => 0,
            'deliveryLongitude' => 0,
        ]);
    }

    public function testCreateOrder_ProductNotFound_Throws(): void
    {
        $restaurant = $this->createMock(Restaurant::class);
        $restaurant->method('isActive')->willReturn(true);
        $this->restaurantRepo->method('find')->willReturn($restaurant);

        $this->productRepo->expects($this->once())->method('find')->with(999)->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found: 999');

        $this->service->createOrder([
            'customerId' => 'c1',
            'restaurantId' => 1,
            'items' => [['productId' => 999, 'quantity' => 1]],
            'deliveryAddress' => 'addr',
            'deliveryLatitude' => 0,
            'deliveryLongitude' => 0,
        ]);
    }

    // ========== cancelOrder tests ==========
    public function testCancelOrderSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('isActive')->willReturn(true);
        $order->expects($this->once())->method('setStatus')->with(Order::STATUS_CANCELLED);
        $this->orderRepo->expects($this->once())->method('find')->with(123)->willReturn($order);
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $result = $this->service->cancelOrder(123, 'Late');
        $this->assertSame($order, $result);
    }

    public function testCancelOrder_NotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->with(123)->willReturn(null);
        $this->orderRepo->expects($this->never())->method('save');
        $result = $this->service->cancelOrder(123, 'Reason');
        $this->assertNull($result);
    }

    public function testCancelOrder_Inactive(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('isActive')->willReturn(false);
        $this->orderRepo->expects($this->once())->method('find')->with(123)->willReturn($order);
        $this->orderRepo->expects($this->never())->method('save');

        $result = $this->service->cancelOrder(123, 'Reason');
        $this->assertNull($result);
    }

    // ========== processPayment tests ==========
    public function testProcessPaymentSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getTotal')->willReturn(60.0);
        $order->method('getSubtotal')->willReturn(50.0);
        $order->method('getDeliveryFee')->willReturn(5.0);
        $order->method('getPayment')->willReturn(null);
        $order->expects($this->once())->method('setPayment')->with($this->isInstanceOf(Payment::class));

        $this->orderRepo->expects($this->once())->method('find')->with(123)->willReturn($order);

        $this->paymentRepo->expects($this->exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(Payment::class));
        $this->orderRepo->expects($this->once())->method('save')->with($order);

        $result = $this->service->processPayment(123, ['method' => 'card']);
        $this->assertInstanceOf(Payment::class, $result);
    }

    public function testProcessPayment_OrderNotFound(): void
    {
        $this->orderRepo->expects($this->once())->method('find')->with(123)->willReturn(null);
        $result = $this->service->processPayment(123, []);
        $this->assertNull($result);
    }

    // ========== getOrderByIdempotencyKey tests ==========
    public function testGetOrderByIdempotencyKeyFound(): void
    {
        $order = $this->createMock(Order::class);
        $this->orderRepo->expects($this->once())
            ->method('findByIdempotencyKey')
            ->with('key-123')
            ->willReturn($order);
        $result = $this->service->getOrderByIdempotencyKey('key-123');
        $this->assertSame($order, $result);
    }

    public function testGetOrderByIdempotencyKeyNotFound(): void
    {
        $this->orderRepo->expects($this->once())
            ->method('findByIdempotencyKey')
            ->with('missing')
            ->willReturn(null);
        $result = $this->service->getOrderByIdempotencyKey('missing');
        $this->assertNull($result);
    }
}
