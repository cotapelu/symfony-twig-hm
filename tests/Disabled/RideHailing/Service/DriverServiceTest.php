<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Service;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Payment;
use App\RideHailing\Service\DriverService;
use PHPUnit\Framework\TestCase;

class DriverServiceTest extends TestCase
{
    public function testDriverCreation(): void
    {
        $driver = new Driver();
        $driver->setUserId('user-123');
        $driver->setLicensePlate('51A-12345');
        $driver->setVehicleType('car');
        $driver->setVehicleBrand('Toyota');
        $driver->setVehicleModel('Vios');
        $driver->setVehicleColor('white');
        $driver->setVehicleYear(2023);
        $driver->setRating(4.8);
        $driver->setStatus(Driver::STATUS_ONLINE);

        $this->assertEquals('user-123', $driver->getUserId());
        $this->assertEquals('51A-12345', $driver->getLicensePlate());
        $this->assertEquals('car', $driver->getVehicleType());
        $this->assertEquals('Toyota', $driver->getVehicleBrand());
        $this->assertEquals('Vios', $driver->getVehicleModel());
        $this->assertEquals('white', $driver->getVehicleColor());
        $this->assertEquals(2023, $driver->getVehicleYear());
        $this->assertEquals(4.8, $driver->getRating());
    }

    public function testDriverDefaultStatus(): void
    {
        $driver = new Driver();
        $this->assertEquals(Driver::STATUS_OFFLINE, $driver->getStatus());
    }

    public function testDriverStatusChange(): void
    {
        $driver = new Driver();

        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertEquals(Driver::STATUS_ONLINE, $driver->getStatus());

        $driver->setStatus(Driver::STATUS_BUSY);
        $this->assertEquals(Driver::STATUS_BUSY, $driver->getStatus());
    }

    public function testDriverDefaultRating(): void
    {
        $driver = new Driver();
        $this->assertEquals(5.00, $driver->getRating());
    }

    public function testDriverLocationUpdates(): void
    {
        $driver = new Driver();

        $driver->setCurrentLatitude(10.8231);
        $driver->setCurrentLongitude(106.6297);

        $this->assertEquals(10.8231, $driver->getCurrentLatitude());
        $this->assertEquals(106.6297, $driver->getCurrentLongitude());
    }

    public function testDriverRideCounts(): void
    {
        $driver = new Driver();

        $driver->setTotalRides(100);
        $driver->setCompletedRides(95);
        $driver->setCancelledRides(5);

        $this->assertEquals(100, $driver->getTotalRides());
        $this->assertEquals(95, $driver->getCompletedRides());
        $this->assertEquals(5, $driver->getCancelledRides());
    }

    public function testDriverVerification(): void
    {
        $driver = new Driver();

        $this->assertFalse($driver->isVerified());
        $driver->setVerified(true);
        $this->assertTrue($driver->isVerified());
    }

    public function testDriverActiveStatus(): void
    {
        $driver = new Driver();

        $this->assertTrue($driver->isActive());
        $driver->setActive(false);
        $this->assertFalse($driver->isActive());
    }

    public function testDriverIsAvailable(): void
    {
        $driver = new Driver();

        $this->assertFalse($driver->isAvailable());
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertTrue($driver->isAvailable());
        $driver->setActive(false);
        $this->assertFalse($driver->isAvailable());
    }

    public function testDriverIsOnline(): void
    {
        $driver = new Driver();

        $this->assertFalse($driver->isOnline());
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertTrue($driver->isOnline());
    }

    public function testDriverBankDetails(): void
    {
        $driver = new Driver();

        $driver->setBankAccountNumber('1234567890');
        $driver->setBankName('Vietcombank');
        $driver->setBankAccountName('John Doe');

        $this->assertEquals('1234567890', $driver->getBankAccountNumber());
        $this->assertEquals('Vietcombank', $driver->getBankName());
        $this->assertEquals('John Doe', $driver->getBankAccountName());
    }

    public function testDriverLastActiveAt(): void
    {
        $driver = new Driver();

        $this->assertInstanceOf(\DateTime::class, $driver->getLastActiveAt());
        $newTime = new \DateTime();
        $driver->setLastActiveAt($newTime);
        $this->assertEquals($newTime, $driver->getLastActiveAt());
    }

    public function testDriverStatusConstants(): void
    {
        $this->assertEquals('offline', Driver::STATUS_OFFLINE);
        $this->assertEquals('online', Driver::STATUS_ONLINE);
        $this->assertEquals('busy', Driver::STATUS_BUSY);
        $this->assertEquals('ride_request', Driver::STATUS_RIDE_REQUEST);
    }

    public function testDriverFluentInterface(): void
    {
        $driver = new Driver();

        $result = $driver
            ->setUserId('user-1')
            ->setLicensePlate('51A-11111')
            ->setVehicleType('car')
            ->setStatus(Driver::STATUS_ONLINE);

        $this->assertInstanceOf(Driver::class, $result);
    }
}