<?php declare(strict_types=1);

namespace App\Tests\Unit\GroceryDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\GroceryDelivery\Entity\DeliveryPerson;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonTest extends TestCase
{
    public function testConstructorSetsCreatedAtAndLastActiveAt(): void
    {
        $dp = new DeliveryPerson();
        $this->assertInstanceOf(\DateTime::class, $dp->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $dp->getLastActiveAt());
    }

    public function testGettersSetters(): void
    {
        $dp = new DeliveryPerson();
        $dp->setUserId('user-789');
        $this->assertSame('user-789', $dp->getUserId());

        $dp->setName('John Delivery');
        $this->assertSame('John Delivery', $dp->getName());

        $dp->setPhone('+0987654321');
        $this->assertSame('+0987654321', $dp->getPhone());

        $dp->setPhone(null);
        $this->assertNull($dp->getPhone());

        $dp->setVehicleType('motorcycle');
        $this->assertSame('motorcycle', $dp->getVehicleType());

        $dp->setLicensePlate('ABC-123');
        $this->assertSame('ABC-123', $dp->getLicensePlate());

        $dp->setLicensePlate(null);
        $this->assertNull($dp->getLicensePlate());

        $dp->setStatus(DeliveryPerson::STATUS_BUSY);
        $this->assertSame(DeliveryPerson::STATUS_BUSY, $dp->getStatus());

        $dp->setCurrentLatitude(10.123456);
        $this->assertSame(10.123456, $dp->getCurrentLatitude());

        $dp->setCurrentLongitude(20.654321);
        $this->assertSame(20.654321, $dp->getCurrentLongitude());

        $dp->setRating(4.8);
        $this->assertSame(4.8, $dp->getRating());

        $dp->setCompletedDeliveries(150);
        $this->assertSame(150, $dp->getCompletedDeliveries());

        $dp->setActive(false);
        $this->assertFalse($dp->isActive());

        $lastActive = new \DateTime('2025-01-01 12:00:00');
        $dp->setLastActiveAt($lastActive);
        $this->assertSame($lastActive, $dp->getLastActiveAt());
    }

    public function testIsOnline(): void
    {
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $this->assertTrue($dp->isOnline());
        $dp->setStatus(DeliveryPerson::STATUS_OFFLINE);
        $this->assertFalse($dp->isOnline());
    }

    public function testIsAvailable(): void
    {
        $dp = new DeliveryPerson();
        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $dp->setActive(true);
        $this->assertTrue($dp->isAvailable());
        $dp->setActive(false);
        $this->assertFalse($dp->isAvailable());
        $dp->setActive(true);
        $dp->setStatus(DeliveryPerson::STATUS_BUSY);
        $this->assertFalse($dp->isAvailable());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('offline', DeliveryPerson::STATUS_OFFLINE);
        $this->assertSame('online', DeliveryPerson::STATUS_ONLINE);
        $this->assertSame('busy', DeliveryPerson::STATUS_BUSY);
    }

    public function testIdSetAndGet(): void
    {
        $dp = new DeliveryPerson();
        $ref = new \ReflectionProperty($dp, 'id');
        $ref->setAccessible(true);
        $ref->setValue($dp, 42);
        $this->assertSame(42, $dp->getId());
    }
}
