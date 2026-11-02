<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Repository\RideRepository;
use App\RideHailing\Repository\DriverRepository;
use App\RideHailing\Repository\PaymentRepository;
use App\RideHailing\Service\RideHailingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RideHailingServiceTest extends TestCase
{
    private RideRepository&MockObject $rideRepo;
    private DriverRepository&MockObject $driverRepo;
    private PaymentRepository&MockObject $paymentRepo;
    private RideHailingService $service;

    protected function setUp(): void
    {
        $this->rideRepo = $this->createMock(RideRepository::class);
        $this->driverRepo = $this->createMock(DriverRepository::class);
        $this->paymentRepo = $this->createMock(PaymentRepository::class);

        $this->service = new RideHailingService(
            $this->rideRepo,
            $this->driverRepo,
            $this->paymentRepo
        );
    }

    public function testFindNearbyDrivers(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $vehicleType = 'sedan';
        $drivers = [$this->createMock(Driver::class), $this->createMock(Driver::class)];
        $this->driverRepo->expects($this->once())
            ->method('findAvailableDrivers')
            ->with($lat, $lng, $radius, $vehicleType)
            ->willReturn($drivers);
        $result = $this->service->findNearbyDrivers($lat, $lng, $radius, $vehicleType);
        $this->assertSame($drivers, $result);
    }

    public function testFindNearbyDriversWithoutVehicleType(): void
    {
        $lat = 10.0;
        $lng = 20.0;
        $radius = 5.0;
        $drivers = [new Driver()];
        $this->driverRepo->expects($this->once())
            ->method('findAvailableDrivers')
            ->with($lat, $lng, $radius, null)
            ->willReturn($drivers);
        $result = $this->service->findNearbyDrivers($lat, $lng, $radius);
        $this->assertSame($drivers, $result);
    }

    public function testGetRideHistory(): void
    {
        $userId = 'user-123';
        $rides = [$this->createMock(Ride::class), $this->createMock(Ride::class)];
        $this->rideRepo->expects($this->once())
            ->method('findCompletedRidesByUser')
            ->with($userId)
            ->willReturn($rides);
        $result = $this->service->getRideHistory($userId);
        $this->assertSame($rides, $result);
    }

    public function testGetActiveRide(): void
    {
        $userId = 'user-123';
        $rides = [$this->createMock(Ride::class)];
        $this->rideRepo->expects($this->once())
            ->method('findActiveRidesByRider')
            ->with($userId)
            ->willReturn($rides);
        $result = $this->service->getActiveRide($userId);
        $this->assertSame($rides[0], $result);
    }

    public function testGetActiveRideWhenNone(): void
    {
        $userId = 'user-123';
        $this->rideRepo->expects($this->once())
            ->method('findActiveRidesByRider')
            ->with($userId)
            ->willReturn([]);
        $result = $this->service->getActiveRide($userId);
        $this->assertNull($result);
    }

    public function testGetDriverActiveRide(): void
    {
        $driverId = 456;
        $rides = [$this->createMock(Ride::class)];
        $this->rideRepo->expects($this->once())
            ->method('findActiveRidesByDriver')
            ->with((string) $driverId)
            ->willReturn($rides);
        $result = $this->service->getDriverActiveRide($driverId);
        $this->assertSame($rides[0], $result);
    }

    public function testGetDriverActiveRideWhenNone(): void
    {
        $driverId = 456;
        $this->rideRepo->expects($this->once())
            ->method('findActiveRidesByDriver')
            ->with((string) $driverId)
            ->willReturn([]);
        $result = $this->service->getDriverActiveRide($driverId);
        $this->assertNull($result);
    }

    public function testUpdateDriverLocation(): void
    {
        $driverId = 789;
        $lat = 40.0;
        $lng = 70.0;
        $driver = $this->createMock(Driver::class);
        $driver->expects($this->once())->method('setCurrentLatitude')->with($lat);
        $driver->expects($this->once())->method('setCurrentLongitude')->with($lng);
        $driver->expects($this->once())->method('setLastActiveAt');
        $this->driverRepo->expects($this->once())->method('find')->with($driverId)->willReturn($driver);
        $this->service->updateDriverLocation($driverId, $lat, $lng);
    }

    public function testUpdateDriverLocationWhenDriverNotFound(): void
    {
        $driverId = 999;
        $this->driverRepo->expects($this->once())->method('find')->with($driverId)->willReturn(null);
        $this->service->updateDriverLocation($driverId, 1.0, 2.0);
        $this->assertTrue(true); // no exception thrown
    }
}
