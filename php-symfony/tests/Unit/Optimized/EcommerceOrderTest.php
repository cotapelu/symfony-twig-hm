<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\EcommerceOrder;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class EcommerceOrderTest extends TestCase
{
    private function createOrder(?string $id = null, ?string $address = null): EcommerceOrder
    {
        $id = $id ?? 'test-order-id';
        $address = $address ?? '123 Test St';
        return new EcommerceOrder($id, 'buyer-1', 'product-1', 2, 5000, $address);
    }

    public function testConstructorInitializesProperties(): void
    {
        $order = $this->createOrder();

        $this->assertSame('test-order-id', $order->getId());
        $this->assertSame('buyer-1', $order->getBuyerId());
        $this->assertSame('product-1', $order->getProductId());
        $this->assertSame(2, $order->getQuantity());
        $this->assertSame(5000, $order->getPriceCents());
        $this->assertSame('123 Test St', $order->getShippingAddress());
        $this->assertSame(EcommerceOrder::STATUS_PENDING, $order->getStatus());
        $this->assertNull($order->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
    }

    public function testGettersAndSetterForStatus(): void
    {
        $order = $this->createOrder();

        $order->setStatus(EcommerceOrder::STATUS_PAID);
        $this->assertSame(EcommerceOrder::STATUS_PAID, $order->getStatus());

        $order->setStatus(EcommerceOrder::STATUS_SHIPPING);
        $this->assertSame(EcommerceOrder::STATUS_SHIPPING, $order->getStatus());

        $order->setStatus(EcommerceOrder::STATUS_DELIVERED);
        $this->assertSame(EcommerceOrder::STATUS_DELIVERED, $order->getStatus());

        $order->setStatus(EcommerceOrder::STATUS_CANCELLED);
        $this->assertSame(EcommerceOrder::STATUS_CANCELLED, $order->getStatus());
    }

    public function testStatusConstantsDefined(): void
    {
        $this->assertSame('PENDING', EcommerceOrder::STATUS_PENDING);
        $this->assertSame('PAID', EcommerceOrder::STATUS_PAID);
        $this->assertSame('SHIPPING', EcommerceOrder::STATUS_SHIPPING);
        $this->assertSame('DELIVERED', EcommerceOrder::STATUS_DELIVERED);
        $this->assertSame('CANCELLED', EcommerceOrder::STATUS_CANCELLED);
    }
}
