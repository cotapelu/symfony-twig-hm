<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryOrder;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderTest extends TestCase
{
    private function createOrder(
        string $id = 'order-id',
        string $storeId = 'store-1',
        string $customerId = 'customer-1',
        string $type = 'RESTAURANT',
        int $totalCents = 2000
    ): DeliveryOrder {
        return new DeliveryOrder($id, $storeId, $customerId, $type, $totalCents);
    }

    public function testConstructor(): void
    {
        $o = $this->createOrder();
        $this->assertSame('order-id', $o->getId());
        $this->assertSame('store-1', $o->getStoreId());
        $this->assertSame('customer-1', $o->getCustomerId());
        $this->assertSame('RESTAURANT', $o->getType());
        $this->assertSame(2000, $o->getTotalCents());
        $this->assertNull($o->getDeliveryAddress());
        $this->assertNull($o->getDeliveryPersonId());
        $this->assertNull($o->getMetadata());
        $this->assertSame('PENDING', $o->getStatus());
        $this->assertInstanceOf(\DateTime::class, $o->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $o->getUpdatedAt());
        $this->assertTrue($o->isPending());
        $this->assertFalse($o->isConfirmed());
        $this->assertFalse($o->isProcessing());
        $this->assertFalse($o->isDelivered());
        $this->assertFalse($o->isCancelled());
        $this->assertFalse($o->isRefunded());
        $this->assertTrue($o->isRestaurant());
        $this->assertFalse($o->isPharmacy());
        $this->assertFalse($o->isGrocery());
        $this->assertFalse($o->hasDeliveryPerson());
    }

    public function testSetStoreId(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setStoreId('store-2');
        $this->assertSame('store-2', $o->getStoreId());
        $this->assertNotEquals($original, $o->getUpdatedAt());
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

    public function testSetType(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setType('PHARMACY');
        $this->assertSame('PHARMACY', $o->getType());
        $this->assertTrue($o->isPharmacy());
        $this->assertFalse($o->isRestaurant());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $o = $this->createOrder();
        $o->setStatus('CONFIRMED');
        $this->assertSame('CONFIRMED', $o->getStatus());
        $this->assertTrue($o->isConfirmed());
        $this->assertFalse($o->isPending());

        $o->setStatus('PROCESSING');
        $this->assertTrue($o->isProcessing());
        $o->setStatus('DELIVERED');
        $this->assertTrue($o->isDelivered());
        $o->setStatus('CANCELLED');
        $this->assertTrue($o->isCancelled());
        $o->setStatus('REFUNDED');
        $this->assertTrue($o->isRefunded());
    }

    public function testSetDeliveryAddress(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $addr = ['address' => '123 St', 'city' => 'Hanoi'];
        $o->setDeliveryAddress($addr);
        $this->assertSame($addr, $o->getDeliveryAddress());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setDeliveryAddress(null);
        $this->assertNull($o->getDeliveryAddress());
    }

    public function testSetDeliveryPersonId(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setDeliveryPersonId('person-1');
        $this->assertSame('person-1', $o->getDeliveryPersonId());
        $this->assertTrue($o->hasDeliveryPerson());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setDeliveryPersonId(null);
        $this->assertNull($o->getDeliveryPersonId());
        $this->assertFalse($o->hasDeliveryPerson());
    }

    public function testSetTotalCents(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $o->setTotalCents(5000);
        $this->assertSame(5000, $o->getTotalCents());
        $this->assertNotEquals($original, $o->getUpdatedAt());
    }

    public function testSetMetadata(): void
    {
        $o = $this->createOrder();
        $original = $o->getUpdatedAt();
        usleep(1000);
        $meta = ['notes' => 'Fragile'];
        $o->setMetadata($meta);
        $this->assertSame($meta, $o->getMetadata());
        $this->assertNotEquals($original, $o->getUpdatedAt());
        $o->setMetadata(null);
        $this->assertNull($o->getMetadata());
    }
}
