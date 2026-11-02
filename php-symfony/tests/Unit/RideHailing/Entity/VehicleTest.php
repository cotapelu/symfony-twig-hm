<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Vehicle;
use App\RideHailing\Entity\Driver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class VehicleTest extends TestCase
{
    private function createMockDriver(): MockObject&Driver
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('getId')->willReturn(1);
        return $driver;
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\DateTime::class, $vehicle->getCreatedAt());
    }

    public function testGettersSetters(): void
    {
        $driver = $this->createMockDriver();
        $vehicle = new Vehicle();

        $vehicle->setDriver($driver);
        $this->assertSame($driver, $vehicle->getDriver());

        $vehicle->setLicensePlate('ABC-123');
        $this->assertSame('ABC-123', $vehicle->getLicensePlate());

        $vehicle->setType('motorcycle');
        $this->assertSame('motorcycle', $vehicle->getType());

        $vehicle->setBrand('Honda');
        $this->assertSame('Honda', $vehicle->getBrand());

        $vehicle->setModel('Wave');
        $this->assertSame('Wave', $vehicle->getModel());

        $vehicle->setColor('Red');
        $this->assertSame('Red', $vehicle->getColor());

        $vehicle->setYear(2020);
        $this->assertSame(2020, $vehicle->getYear());

        $vehicle->setYear(null);
        $this->assertNull($vehicle->getYear());

        $vehicle->setImageUrl('https://example.com/vehicle.jpg');
        $this->assertSame('https://example.com/vehicle.jpg', $vehicle->getImageUrl());

        $vehicle->setDefault(false);
        $this->assertFalse($vehicle->isDefault());

        $vehicle->setVerified(true);
        $this->assertTrue($vehicle->isVerified());
    }

    public function testIdSetAndGet(): void
    {
        $vehicle = new Vehicle();
        $ref = new \ReflectionProperty($vehicle, 'id');
        $ref->setAccessible(true);
        $ref->setValue($vehicle, 123);
        $this->assertSame(123, $vehicle->getId());
    }
}
