<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Driver;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DriverTest extends TestCase
{
    private function createDriver(): Driver
    {
        return new Driver();
    }

    // Helper to set all required non-nullable fields for a usable driver
    private function populateDriver(Driver $driver): void
    {
        $driver->setUserId('user-123');
        $driver->setLicensePlate('ABC-123');
        $driver->setVehicleType('Motorcycle');
        $driver->setVehicleBrand('Honda');
        $driver->setVehicleModel('SH');
        $driver->setVehicleColor('White');
        $driver->setCurrentLatitude(10.0);
        $driver->setCurrentLongitude(20.0);
    }

    public function testConstructorInitializesProperties(): void
    {
        $driver = $this->createDriver();
        $this->assertInstanceOf(\DateTime::class, $driver->getLastActiveAt());
        $this->assertSame(5.00, $driver->getRating());
        $this->assertSame(0, $driver->getTotalRides());
        $this->assertSame(0, $driver->getCompletedRides());
        $this->assertSame(0, $driver->getCancelledRides());
        $this->assertFalse($driver->isVerified());
        $this->assertTrue($driver->isActive());
        $this->assertSame(Driver::STATUS_OFFLINE, $driver->getStatus());
        $this->assertNull($driver->getVehicleYear());
        $this->assertNull($driver->getDocuments());
        $this->assertNull($driver->getBankAccountNumber());
        $this->assertNull($driver->getBankName());
        $this->assertNull($driver->getBankAccountName());
    }

    public function testUserId(): void
    {
        $driver = $this->createDriver();
        $driver->setUserId('user-456');
        $this->assertSame('user-456', $driver->getUserId());
    }

    public function testLicensePlate(): void
    {
        $driver = $this->createDriver();
        $driver->setLicensePlate('XYZ-789');
        $this->assertSame('XYZ-789', $driver->getLicensePlate());
    }

    public function testVehicleDetails(): void
    {
        $driver = $this->createDriver();
        $driver->setVehicleType('Car');
        $this->assertSame('Car', $driver->getVehicleType());

        $driver->setVehicleBrand('Toyota');
        $this->assertSame('Toyota', $driver->getVehicleBrand());

        $driver->setVehicleModel('Camry');
        $this->assertSame('Camry', $driver->getVehicleModel());

        $driver->setVehicleColor('Black');
        $this->assertSame('Black', $driver->getVehicleColor());

        $driver->setVehicleYear(2020);
        $this->assertSame(2020, $driver->getVehicleYear());

        $driver->setVehicleYear(null);
        $this->assertNull($driver->getVehicleYear());
    }

    public function testStatusAndFlags(): void
    {
        $driver = $this->createDriver();

        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertSame(Driver::STATUS_ONLINE, $driver->getStatus());
        $this->assertTrue($driver->isOnline());
        $this->assertTrue($driver->isAvailable()); // isActive true by default

        $driver->setStatus(Driver::STATUS_BUSY);
        $this->assertTrue($driver->isOnline());
        $this->assertFalse($driver->isAvailable());

        $driver->setStatus(Driver::STATUS_OFFLINE);
        $this->assertFalse($driver->isOnline());
        $this->assertFalse($driver->isAvailable());

        $driver->setActive(false);
        $this->assertFalse($driver->isActive());
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertFalse($driver->isAvailable()); // inactive even if online
    }

    public function testLocation(): void
    {
        $driver = $this->createDriver();
        $driver->setCurrentLatitude(52.5200);
        $driver->setCurrentLongitude(13.4050);
        $this->assertSame(52.5200, $driver->getCurrentLatitude());
        $this->assertSame(13.4050, $driver->getCurrentLongitude());
    }

    public function testRating(): void
    {
        $driver = $this->createDriver();
        $this->assertSame(5.00, $driver->getRating());
        $driver->setRating(4.8);
        $this->assertSame(4.8, $driver->getRating());
    }

    public function testRideCounts(): void
    {
        $driver = $this->createDriver();
        $this->assertSame(0, $driver->getTotalRides());
        $this->assertSame(0, $driver->getCompletedRides());
        $this->assertSame(0, $driver->getCancelledRides());

        $driver->setTotalRides(150);
        $driver->setCompletedRides(140);
        $driver->setCancelledRides(10);
        $this->assertSame(150, $driver->getTotalRides());
        $this->assertSame(140, $driver->getCompletedRides());
        $this->assertSame(10, $driver->getCancelledRides());
    }

    public function testVerification(): void
    {
        $driver = $this->createDriver();
        $this->assertFalse($driver->isVerified());
        $driver->setVerified(true);
        $this->assertTrue($driver->isVerified());
    }

    public function testActiveFlag(): void
    {
        $driver = $this->createDriver();
        $this->assertTrue($driver->isActive());
        $driver->setActive(false);
        $this->assertFalse($driver->isActive());
    }

    public function testLastActiveAt(): void
    {
        $driver = $this->createDriver();
        $this->assertInstanceOf(\DateTime::class, $driver->getLastActiveAt());

        $dt = new \DateTime('2020-01-01');
        $driver->setLastActiveAt($dt);
        $this->assertSame($dt, $driver->getLastActiveAt());

        $driver->setLastActiveAt(null);
        $this->assertNull($driver->getLastActiveAt());
    }

    public function testBankingDetails(): void
    {
        $driver = $this->createDriver();
        $driver->setBankAccountNumber('123456789');
        $this->assertSame('123456789', $driver->getBankAccountNumber());

        $driver->setBankName('MyBank');
        $this->assertSame('MyBank', $driver->getBankName());

        $driver->setBankAccountName('John Doe');
        $this->assertSame('John Doe', $driver->getBankAccountName());

        $driver->setBankAccountNumber(null);
        $this->assertNull($driver->getBankAccountNumber());
    }

    public function testDocuments(): void
    {
        $driver = $this->createDriver();
        $driver->setDocuments('license_url');
        $this->assertSame('license_url', $driver->getDocuments());
        $driver->setDocuments(null);
        $this->assertNull($driver->getDocuments());
    }
}
