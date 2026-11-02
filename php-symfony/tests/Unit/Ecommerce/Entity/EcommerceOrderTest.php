<?php declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Ecommerce\Entity\EcommerceOrder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceOrderTest extends TestCase
{
    private function createOrder(?\DateTime $createdAt = null): EcommerceOrder
    {
        return new EcommerceOrder(createdAt: $createdAt);
    }

    public function testConstructorInitializesProperties(): void
    {
        $order = $this->createOrder();
        // id, userId, productId uninitialized; avoid access.
        $this->assertSame(1, $order->getQuantity());
        $this->assertSame(0, $order->getTotalPriceInCents());
        $this->assertSame('USD', $order->getCurrency());
        $this->assertSame('pending', $order->getStatus());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $order = $this->createOrder();

        $order->setId('order-123');
        $this->assertSame('order-123', $order->getId());

        $order->setUserId('user-1');
        $this->assertSame('user-1', $order->getUserId());

        $order->setProductId('product-1');
        $this->assertSame('product-1', $order->getProductId());

        $order->setQuantity(3);
        $this->assertSame(3, $order->getQuantity());

        $order->setTotalPriceInCents(2999);
        $this->assertSame(2999, $order->getTotalPriceInCents());

        $order->setCurrency('EUR');
        $this->assertSame('EUR', $order->getCurrency());

        $address = ['street' => '123 Main', 'city' => 'NYC'];
        $order->setShippingAddress($address);
        $this->assertSame($address, $order->getShippingAddress());

        $order->setStatus('confirmed');
        $this->assertSame('confirmed', $order->getStatus());

        $updated = new \DateTime();
        $order->setUpdatedAt($updated);
        $this->assertSame($updated, $order->getUpdatedAt());
    }
}
