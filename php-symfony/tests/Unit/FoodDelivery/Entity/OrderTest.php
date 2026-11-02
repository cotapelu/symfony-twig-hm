<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\Order;
use App\FoodDelivery\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderTest extends TestCase
{
    private function createRestaurant(): Restaurant
    {
        $restaurant = new Restaurant();
        // Set minimal required fields
        $restaurant->setName('Test Restaurant');
        $restaurant->setAddress('123 Test St');
        $restaurant->setLatitude(0.0);
        $restaurant->setLongitude(0.0);
        return $restaurant;
    }

    private function createOrder(?Restaurant $restaurant = null): Order
    {
        return new Order(); // constructor generates orderNumber and createdAt
    }

    public function testConstructorInitializesProperties(): void
    {
        $order = $this->createOrder();
        $this->assertIsString($order->getOrderNumber());
        $this->assertStringStartsWith('FD-', $order->getOrderNumber());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $order->getItems());
        $this->assertCount(0, $order->getItems());
        // Unset required fields (should be uninitialized, so we avoid accessing them)
    }

    public function testSettersAndGetters(): void
    {
        $order = $this->createOrder();
        $restaurant = $this->createRestaurant();

        $order->setRestaurant($restaurant);
        $this->assertSame($restaurant, $order->getRestaurant());

        $order->setCustomerId('customer-123');
        $this->assertSame('customer-123', $order->getCustomerId());

        $order->setSubtotal(100.0);
        $this->assertSame(100.0, $order->getSubtotal());

        $order->setDeliveryFee(5.0);
        $this->assertSame(5.0, $order->getDeliveryFee());

        $order->setServiceFee(2.0);
        $this->assertSame(2.0, $order->getServiceFee());

        $order->setTax(5.0);
        $this->assertSame(5.0, $order->getTax());

        $order->setDiscount(0.0);
        $this->assertSame(0.0, $order->getDiscount());

        $order->setTotal(107.0);
        $this->assertSame(107.0, $order->getTotal());

        $order->setDeliveryAddress('123 Main St');
        $this->assertSame('123 Main St', $order->getDeliveryAddress());

        $order->setDeliveryLatitude(40.7128);
        $this->assertSame(40.7128, $order->getDeliveryLatitude());

        $order->setDeliveryLongitude(-74.0060);
        $this->assertSame(-74.0060, $order->getDeliveryLongitude());

        $order->setDeliveryInstructions('Ring bell');
        $this->assertSame('Ring bell', $order->getDeliveryInstructions());

        // status default pending, but we can change
        $order->setStatus(Order::STATUS_CONFIRMED);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->getStatus());

        // setPayment (nullable)
        $order->setPayment(null);
        $this->assertNull($order->getPayment());
    }
}
