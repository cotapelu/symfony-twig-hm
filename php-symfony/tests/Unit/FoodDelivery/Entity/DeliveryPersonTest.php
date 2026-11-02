<?php declare(strict_types=1);

namespace App\Tests\Unit\FoodDelivery\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FoodDelivery\Entity\DeliveryPerson;
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
        // userId not set (non-nullable) – avoid accessing before set
        $this->assertSame(DeliveryPerson::STATUS_OFFLINE, $dp->getStatus());
        $this->assertSame(0.0, $dp->getCurrentLatitude());
        $this->assertSame(0.0, $dp->getCurrentLongitude());
        $this->assertSame(5.00, $dp->getRating());
        $this->assertSame(0, $dp->getTotalDeliveries());
        $this->assertSame(0, $dp->getCompletedDeliveries());
        $this->assertTrue($dp->isActive());
        $this->assertInstanceOf(\DateTime::class, $dp->getLastActiveAt());
        $this->assertInstanceOf(\DateTime::class, $dp->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $dp = $this->createDeliveryPerson();

        $dp->setUserId('user-1');
        $this->assertSame('user-1', $dp->getUserId());

        $dp->setName('John Doe');
        $this->assertSame('John Doe', $dp->getName());

        $dp->setPhone('+1 555 1234');
        $this->assertSame('+1 555 1234', $dp->getPhone());

        $dp->setVehicleType('Motorcycle');
        $this->assertSame('Motorcycle', $dp->getVehicleType());

        $dp->setLicensePlate('ABC-123');
        $this->assertSame('ABC-123', $dp->getLicensePlate());

        $dp->setStatus(DeliveryPerson::STATUS_ONLINE);
        $this->assertSame(DeliveryPerson::STATUS_ONLINE, $dp->getStatus());

        $dp->setCurrentLatitude(52.5200);
        $this->assertSame(52.5200, $dp->getCurrentLatitude());

        $dp->setCurrentLongitude(13.4050);
        $this->assertSame(13.4050, $dp->getCurrentLongitude());

        $dp->setRating(4.8);
        $this->assertSame(4.8, $dp->getRating());

        $dp->setTotalDeliveries(150);
        $this->assertSame(150, $dp->getTotalDeliveries());

        $dp->setCompletedDeliveries(140);
        $this->assertSame(140, $dp->getCompletedDeliveries());

        $dp->setActive(false);
        $this->assertFalse($dp->isActive());

        $lastActive = new \DateTime();
        $dp->setLastActiveAt($lastActive);
        $this->assertSame($lastActive, $dp->getLastActiveAt());
    }
}
