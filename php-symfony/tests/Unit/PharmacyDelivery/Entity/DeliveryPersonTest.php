<?php declare(strict_types=1);

namespace App\Tests\Unit\PharmacyDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\PharmacyDelivery\Entity\DeliveryPerson;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryPersonTest extends TestCase
{
    private function createDeliveryPerson(): DeliveryPerson
    {
        return new DeliveryPerson();
    }

    public function testConstructorInitializesProperties(): void
    {
        $dp = $this->createDeliveryPerson();
        $this->assertSame('available', $dp->getStatus());
        $this->assertSame(5.0, $dp->getRating());
        $this->assertSame(0, $dp->getTotalDeliveries());
        $this->assertInstanceOf(\DateTime::class, $dp->getCreatedAt());
        // latitude and longitude not initialized, avoid access
    }

    public function testGettersAndSetters(): void
    {
        $dp = $this->createDeliveryPerson();

        $dp->setUserId('user-123');
        $this->assertSame('user-123', $dp->getUserId());

        $dp->setName('John');
        $this->assertSame('John', $dp->getName());

        $dp->setPhone('123-456-7890');
        $this->assertSame('123-456-7890', $dp->getPhone());

        $dp->setEmail('john@example.com');
        $this->assertSame('john@example.com', $dp->getEmail());

        $dp->setEmail(null);
        $this->assertNull($dp->getEmail());

        $dp->setVehicleType('Motorcycle');
        $this->assertSame('Motorcycle', $dp->getVehicleType());

        $dp->setVehicleNumber('ABC-123');
        $this->assertSame('ABC-123', $dp->getVehicleNumber());

        $dp->setVehicleNumber(null);
        $this->assertNull($dp->getVehicleNumber());

        $dp->setStatus('busy');
        $this->assertSame('busy', $dp->getStatus());

        $dp->setLatitude(10.0);
        $dp->setLongitude(20.0);
        $this->assertSame(10.0, $dp->getLatitude());
        $this->assertSame(20.0, $dp->getLongitude());

        $dp->setRating(4.7);
        $this->assertSame(4.7, $dp->getRating());

        $dp->setTotalDeliveries(150);
        $this->assertSame(150, $dp->getTotalDeliveries());
    }
}
