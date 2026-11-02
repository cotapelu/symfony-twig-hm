<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryStore;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryStoreTest extends TestCase
{
    private function createStore(
        string $id = 'store-id',
        string $ownerId = 'owner-1',
        string $type = 'RESTAURANT',
        string $name = 'My Store'
    ): DeliveryStore {
        return new DeliveryStore($id, $ownerId, $type, $name);
    }

    public function testConstructor(): void
    {
        $s = $this->createStore();
        $this->assertSame('store-id', $s->getId());
        $this->assertSame('owner-1', $s->getOwnerId());
        $this->assertSame('RESTAURANT', $s->getType());
        $this->assertSame('My Store', $s->getName());
        $this->assertNull($s->getLicense());
        $this->assertSame('ACTIVE', $s->getStatus());
        $this->assertNull($s->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $s->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $s->getUpdatedAt());
        $this->assertTrue($s->isActive());
        $this->assertFalse($s->isInactive());
        $this->assertFalse($s->isSuspended());
        $this->assertTrue($s->isRestaurant());
        $this->assertFalse($s->isPharmacy());
        $this->assertFalse($s->isGrocery());
    }

    public function testSetOwnerId(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $s->setOwnerId('owner-2');
        $this->assertSame('owner-2', $s->getOwnerId());
        $this->assertNotEquals($original, $s->getUpdatedAt());
    }

    public function testSetType(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $s->setType('GROCERY');
        $this->assertSame('GROCERY', $s->getType());
        $this->assertTrue($s->isGrocery());
        $this->assertFalse($s->isRestaurant());
        $this->assertNotEquals($original, $s->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $s->setName('New Name');
        $this->assertSame('New Name', $s->getName());
        $this->assertNotEquals($original, $s->getUpdatedAt());
    }

    public function testSetLicense(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $s->setLicense('LIC-123');
        $this->assertSame('LIC-123', $s->getLicense());
        $this->assertNotEquals($original, $s->getUpdatedAt());
        $s->setLicense(null);
        $this->assertNull($s->getLicense());
    }

    public function testSetStatus(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $s->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $s->getStatus());
        $this->assertTrue($s->isInactive());
        $this->assertFalse($s->isActive());
        $this->assertNotEquals($original, $s->getUpdatedAt());

        $s->setStatus('SUSPENDED');
        $this->assertTrue($s->isSuspended());
        $this->assertFalse($s->isActive());
    }

    public function testSetMetadata(): void
    {
        $s = $this->createStore();
        $original = $s->getUpdatedAt();
        usleep(1000);
        $meta = ['category' => 'food', 'employees' => 5];
        $s->setMetadata($meta);
        $this->assertSame($meta, $s->getMetadata());
        $this->assertNotEquals($original, $s->getUpdatedAt());
        $s->setMetadata(null);
        $this->assertNull($s->getMetadata());
    }
}
