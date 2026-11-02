<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceOrder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceOrderTest extends TestCase
{
    private function createOrder(
        string $id = 'order-id',
        string $customerId = 'customer-1',
        int $totalCents = 5000
    ): EcommerceOrder {
        return new EcommerceOrder($id, $customerId, $totalCents);
    }

    public function testConstructor(): void
    {
        $o = $this->createOrder();
        $this->assertSame('order-id', $o->getId());
        $this->assertSame('customer-1', $o->getCustomerId());
        $this->assertSame('PENDING', $o->getStatus());
        $this->assertSame(5000, $o->getTotalCents());
        $this->assertSame('USD', $o->getCurrency());
        $this->assertNull($o->getShippingAddress());
        $this->assertNull($o->getBillingAddress());
        $this->assertNull($o->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $o->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $o->getUpdatedAt());
    }

    public function testSetCustomerId(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setCustomerId('customer-2');
        $this->assertSame('customer-2', $o->getCustomerId());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setStatus('PROCESSING');
        $this->assertSame('PROCESSING', $o->getStatus());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetTotalCents(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setTotalCents(10000);
        $this->assertSame(10000, $o->getTotalCents());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetCurrency(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setCurrency('VND');
        $this->assertSame('VND', $o->getCurrency());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetShippingAddress(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $addr = ['address' => '123 St', 'city' => 'Hanoi'];
        $o->setShippingAddress($addr);
        $this->assertSame($addr, $o->getShippingAddress());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setShippingAddress(null);
        $this->assertNull($o->getShippingAddress());
    }

    public function testSetBillingAddress(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $addr = ['address' => '456 St', 'city' => 'HCM'];
        $o->setBillingAddress($addr);
        $this->assertSame($addr, $o->getBillingAddress());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setBillingAddress(null);
        $this->assertNull($o->getBillingAddress());
    }

    public function testSetMetadata(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $meta = ['promo' => 'SALE2025'];
        $o->setMetadata($meta);
        $this->assertSame($meta, $o->getMetadata());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setMetadata(null);
        $this->assertNull($o->getMetadata());
    }
}
