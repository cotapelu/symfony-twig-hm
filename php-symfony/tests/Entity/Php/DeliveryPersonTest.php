<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryPerson;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonTest extends TestCase
{
    private function createPerson(
        string $id = 'person-id',
        string $userId = 'user-1',
        string $name = 'John Doe',
        string $phone = '0123456789'
    ): DeliveryPerson {
        return new DeliveryPerson($id, $userId, $name, $phone);
    }

    public function testConstructor(): void
    {
        $p = $this->createPerson();
        $this->assertSame('person-id', $p->getId());
        $this->assertSame('user-1', $p->getUserId());
        $this->assertSame('John Doe', $p->getName());
        $this->assertSame('0123456789', $p->getPhone());
        $this->assertSame('ACTIVE', $p->getStatus());
        $this->assertNull($p->getVehicleId());
        $this->assertNull($p->getCurrentLocation());
        $this->assertNull($p->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $p->getUpdatedAt());
        $this->assertTrue($p->isActive());
        $this->assertFalse($p->isOffline());
        $this->assertFalse($p->isBusy());
        $this->assertFalse($p->isSuspended());
        $this->assertFalse($p->hasVehicle());
        $this->assertFalse($p->hasCurrentLocation());
    }

    public function testSetUserId(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setUserId('user-2');
        $this->assertSame('user-2', $p->getUserId());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setName('Jane');
        $this->assertSame('Jane', $p->getName());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetPhone(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setPhone('0987654321');
        $this->assertSame('0987654321', $p->getPhone());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $p = $this->createPerson();
        $p->setStatus('BUSY');
        $this->assertSame('BUSY', $p->getStatus());
        $this->assertTrue($p->isBusy());
        $this->assertFalse($p->isActive());
        $this->assertFalse($p->isOffline());
        $this->assertFalse($p->isSuspended());

        $p->setStatus('OFFLINE');
        $this->assertTrue($p->isOffline());
        $this->assertFalse($p->isBusy());

        $p->setStatus('SUSPENDED');
        $this->assertTrue($p->isSuspended());
        $this->assertFalse($p->isActive());
    }

    public function testSetVehicleId(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setVehicleId('vehicle-1');
        $this->assertSame('vehicle-1', $p->getVehicleId());
        $this->assertTrue($p->hasVehicle());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setVehicleId(null);
        $this->assertNull($p->getVehicleId());
        $this->assertFalse($p->hasVehicle());
    }

    public function testSetCurrentLocation(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $loc = ['lat' => 10.0, 'lng' => 106.0];
        $p->setCurrentLocation($loc);
        $this->assertSame($loc, $p->getCurrentLocation());
        $this->assertTrue($p->hasCurrentLocation());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setCurrentLocation(null);
        $this->assertNull($p->getCurrentLocation());
        $this->assertFalse($p->hasCurrentLocation());
    }

    public function testSetMetadata(): void
    {
        $p = $this->createPerson();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $meta = ['rating' => 5, 'notes' => 'Reliable'];
        $p->setMetadata($meta);
        $this->assertSame($meta, $p->getMetadata());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setMetadata(null);
        $this->assertNull($p->getMetadata());
    }
}
