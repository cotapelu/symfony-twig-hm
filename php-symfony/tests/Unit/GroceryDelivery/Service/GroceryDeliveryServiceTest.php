<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\Order;
use App\GroceryDelivery\Entity\OrderItem;
use App\GroceryDelivery\Entity\Payment;
use App\GroceryDelivery\Entity\Store;
use App\GroceryDelivery\Entity\Product;
use App\GroceryDelivery\Repository\OrderRepository;
use App\GroceryDelivery\Repository\StoreRepository;
use App\GroceryDelivery\Repository\ProductRepository;
use App\GroceryDelivery\Repository\PaymentRepository;
use App\GroceryDelivery\Service\GroceryDeliveryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GroceryDeliveryServiceTest extends TestCase
{
    private StoreRepository&MockObject $storeRepo;
    private ProductRepository&MockObject $productRepo;
    private OrderRepository&MockObject $orderRepo;
    private PaymentRepository&MockObject $paymentRepo;
    private GroceryDeliveryService $service;

    protected function setUp(): void
    {
        $this->storeRepo = $this->createMock(StoreRepository::class);
        $this->productRepo = $this->createMock(ProductRepository::class);
        $this->orderRepo = $this->createMock(OrderRepository::class);
        $this->paymentRepo = $this->createMock(PaymentRepository::class);

        $this->service = new GroceryDeliveryService(
            $this->orderRepo,
            $this->storeRepo,
            $this->productRepo,
            $this->paymentRepo,
        );
    }

    public function testCreateOrderSuccess(): void
    {
        $storeId = 'store-123';
        $customerId = 'user-123';
        $data = [
            'storeId' => $storeId,
            'customerId' => $customerId,
            'items' => [
                ['productId' => 'prod-1', 'quantity' => 2],
                ['productId' => 'prod-2', 'quantity' => 1],
            ],
            'deliveryAddress' => '123 Main St',
            'lat' => 10.0,
            'lng' => 20.0,
            'discount' => 5.0,
        ];

        // Mock store
        $store = $this->createMock(Store::class);
        $store->method('isActive')->willReturn(true);
        $store->method('getDeliveryFee')->willReturn(5.0);
        $store->method('getDeliveryTime')->willReturn(30);

        $this->storeRepo->expects($this->once())
            ->method('find')
            ->with($storeId)
            ->willReturn($store);

        // Mock products
        $product1 = $this->createMock(Product::class);
        $product1->method('getPrice')->willReturn(10.0);
        $product2 = $this->createMock(Product::class);
        $product2->method('getPrice')->willReturn(15.0);

        $this->productRepo->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($product1, $product2);

        // Expect order save once
        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Order $order) use ($customerId, $store, $data) {
                $this->assertSame($customerId, $order->getCustomerId());
                $this->assertSame($store, $order->getStore());
                // Check items count
                $this->assertCount(2, $order->getItems());
                return true;
            }));

        // No payment involved
        $this->paymentRepo->expects($this->never())
            ->method('save');

        $order = $this->service->createOrder($data);
        $this->assertInstanceOf(Order::class, $order);
    }

    public function testCreateOrderFailsWhenStoreNotFound(): void
    {
        $storeId = 'store-x';
        $data = [
            'storeId' => $storeId,
            'customerId' => 'user-1',
            'items' => [],
            'deliveryAddress' => 'Addr',
            'lat' => 0,
            'lng' => 0,
        ];

        $this->storeRepo->expects($this->once())
            ->method('find')
            ->with($storeId)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store invalid');

        $this->service->createOrder($data);
    }

    public function testCreateOrderFailsWhenStoreInactive(): void
    {
        $storeId = 'store-123';
        $data = [
            'storeId' => $storeId,
            'customerId' => 'user-1',
            'items' => [],
            'deliveryAddress' => 'Addr',
            'lat' => 0,
            'lng' => 0,
        ];

        $store = $this->createMock(Store::class);
        $store->method('isActive')->willReturn(false);

        $this->storeRepo->expects($this->once())
            ->method('find')
            ->with($storeId)
            ->willReturn($store);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store invalid');

        $this->service->createOrder($data);
    }

    public function testCreateOrderFailsWhenProductNotFound(): void
    {
        $storeId = 'store-123';
        $data = [
            'storeId' => $storeId,
            'customerId' => 'user-1',
            'items' => [
                ['productId' => 'prod-missing', 'quantity' => 1],
            ],
            'deliveryAddress' => 'Addr',
            'lat' => 0,
            'lng' => 0,
        ];

        $store = $this->createMock(Store::class);
        $store->method('isActive')->willReturn(true);
        $this->storeRepo->method('find')->with($storeId)->willReturn($store);

        $this->productRepo->expects($this->once())
            ->method('find')
            ->with('prod-missing')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found');

        $this->service->createOrder($data);
    }

    public function testConfirmOrderWhenPending(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_PENDING);
        $store = $this->createMock(Store::class);
        $store->method('getDeliveryTime')->willReturn(30);
        $order->setStore($store);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($o) {
                return $o->getStatus() === Order::STATUS_CONFIRMED
                    && $o->getEstimatedDeliveryTime() === 45; // 30+15
            }));

        $result = $this->service->confirmOrder($orderId);
        $this->assertSame($order, $result);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->getStatus());
    }

    public function testConfirmOrderReturnsNullWhenNotFound(): void
    {
        $orderId = 999;
        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn(null);

        $result = $this->service->confirmOrder($orderId);
        $this->assertNull($result);
    }

    public function testConfirmOrderReturnsNullWhenNotPending(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_CONFIRMED);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $result = $this->service->confirmOrder($orderId);
        $this->assertNull($result);
    }

    public function testReadyOrderWhenConfirmed(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_CONFIRMED);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($o) => $o->getStatus() === Order::STATUS_READY));

        $result = $this->service->readyOrder($orderId);
        $this->assertSame($order, $result);
    }

    public function testReadyOrderReturnsNullWhenWrongStatus(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_PENDING);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $result = $this->service->readyOrder($orderId);
        $this->assertNull($result);
    }

    public function testCompleteDeliveryWhenPickedUp(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_PICKED_UP);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($o) => $o->getStatus() === Order::STATUS_DELIVERED));

        $result = $this->service->completeDelivery($orderId);
        $this->assertSame($order, $result);
    }

    public function testCompleteDeliveryReturnsNullWhenNotPickedUp(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_READY);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $result = $this->service->completeDelivery($orderId);
        $this->assertNull($result);
    }

    public function testCancelOrderWhenActive(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_PENDING);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($o) => $o->getStatus() === Order::STATUS_CANCELLED));

        $result = $this->service->cancelOrder($orderId);
        $this->assertSame($order, $result);
    }

    public function testCancelOrderReturnsNullWhenNotActive(): void
    {
        $orderId = 1;
        $order = new Order();
        $order->setStatus(Order::STATUS_DELIVERED);

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $result = $this->service->cancelOrder($orderId);
        $this->assertNull($result);
    }

    public function testProcessPaymentCreatesPaymentAndUpdatesOrder(): void
    {
        $orderId = 1;
        $data = ['method' => Payment::METHOD_CARD];

        $order = new Order();
        // Set necessary fields to calculate total? The service uses $order->getTotal()
        // Let's set subtotal etc for completeness but service uses $order->getTotal() directly for Payment amount.
        $order->setSubtotal(100.0);
        $order->setDeliveryFee(5.0);
        $order->setDiscount(0);
        $order->setTotal(110.0); // Actually service calculations: total = subtotal + deliveryFee + (subtotal*0.1) - discount. In createOrder they compute total. But for processPayment test, we only need the total that the order has. We'll set manually.

        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        // Payment save expectations
        $this->paymentRepo->expects($this->exactly(2))
            ->method('save');

        $this->orderRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($o) {
                return $o->getPayment() !== null;
            }));

        $payment = $this->service->processPayment($orderId, $data);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame($order, $payment->getOrder());
        $this->assertSame(110.0, $payment->getAmount());
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->getStatus());
        // Platform fee = subtotal * 0.15 = 15, store earning = subtotal * 0.85 = 85, driver earning = deliveryFee * 0.9 = 4.5
        $this->assertSame(15.0, $payment->getPlatformFee());
        $this->assertSame(85.0, $payment->getStoreEarning());
        $this->assertSame(4.5, $payment->getDriverEarning());
    }

    public function testGetNearbyStores(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $stores = [$this->createMock(Store::class)];

        $this->storeRepo->expects($this->once())
            ->method('findNearbyStores')
            ->with($lat, $lng, $radius)
            ->willReturn($stores);

        $result = $this->service->getNearbyStores($lat, $lng, $radius);
        $this->assertSame($stores, $result);
    }

    public function testGetNearbyStoresDefaultRadius(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $stores = [$this->createMock(Store::class)];

        $this->storeRepo->expects($this->once())
            ->method('findNearbyStores')
            ->with($lat, $lng, 5.0) // default 5
            ->willReturn($stores);

        $result = $this->service->getNearbyStores($lat, $lng);
        $this->assertSame($stores, $result);
    }

    public function testGetCustomerOrders(): void
    {
        $userId = 'user-123';
        $orders = [new Order(), new Order()];
        $this->orderRepo->expects($this->once())
            ->method('findByCustomer')
            ->with($userId)
            ->willReturn($orders);

        $result = $this->service->getCustomerOrders($userId);
        $this->assertSame($orders, $result);
    }

    public function testGetOrder(): void
    {
        $orderId = 1;
        $order = new Order();
        $this->orderRepo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);

        $result = $this->service->getOrder($orderId);
        $this->assertSame($order, $result);
    }
}
