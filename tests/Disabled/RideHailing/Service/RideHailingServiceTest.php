<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Service;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Payment;
use App\RideHailing\Repository\DriverRepository;
use App\RideHailing\Repository\PaymentRepository;
use App\RideHailing\Repository\RideRepository;
use App\RideHailing\Service\RideHailingService;
use PHPUnit\Framework\TestCase;

class RideHailingServiceTest extends TestCase
{
    private RideHailingService $service;
    private RideRepository $rideRepo;
    private DriverRepository $driverRepo;
    private PaymentRepository $paymentRepo;

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

    public function testRequestRideCreatesRide(): void
    {
        $data = [
            'riderId' => 'rider-123',
            'pickupLatitude' => 10.8231,
            'pickupLongitude' => 106.6297,
            'pickupAddress' => '123 Nguyen Hue, District 1, HCMC',
            'dropoffLatitude' => 10.7769,
            'dropoffLongitude' => 106.7000,
            'dropoffAddress' => '456 Le Loi, District 1, HCMC',
            'estimatedPrice' => 50000.00,
            'estimatedDuration' => 1800,
            'distance' => 5000,
            'notes' => 'Please drive carefully',
        ];

        $ride = $this->service->requestRide($data);

        $this->assertInstanceOf(Ride::class, $ride);
        $this->assertEquals('rider-123', $ride->getRiderId());
        $this->assertEquals(Ride::STATUS_REQUESTED, $ride->getStatus());
        $this->assertEquals(Ride::TYPE_STANDARD, $ride->getRideType());
    }

    public function testRequestRideWithCustomRideType(): void
    {
        $data = [
            'riderId' => 'rider-123',
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Test Address',
            'estimatedPrice' => 100000.00,
            'estimatedDuration' => 3600,
            'rideType' => Ride::TYPE_PREMIUM,
        ];

        $ride = $this->service->requestRide($data);

        $this->assertEquals(Ride::TYPE_PREMIUM, $ride->getRideType());
    }

    public function testAcceptRideReturnsRideWhenSuccessful(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-123');
        $ride->setStatus(Ride::STATUS_REQUESTED);

        $driver = new Driver();
        $driver->setUserId('driver-123');
        $driver->setStatus(Driver::STATUS_ONLINE);
        $driver->setActive(true);

        $this->rideRepo->method('find')->willReturn($ride);
        $this->driverRepo->method('find')->willReturn($driver);

        $result = $this->service->acceptRide(1, 1);

        $this->assertNotNull($result);
        $this->assertEquals(Ride::STATUS_ACCEPTED, $result->getStatus());
        $this->assertEquals(Driver::STATUS_BUSY, $driver->getStatus());
    }

    public function testAcceptRideReturnsNullWhenRideNotFound(): void
    {
        $this->rideRepo->method('find')->willReturn(null);

        $result = $this->service->acceptRide(999, 1);

        $this->assertNull($result);
    }

    public function testAcceptRideReturnsNullWhenRideNotRequested(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_COMPLETED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->acceptRide(1, 1);

        $this->assertNull($result);
    }

    public function testAcceptRideReturnsNullWhenDriverNotAvailable(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_REQUESTED);

        $driver = new Driver();
        $driver->setStatus(Driver::STATUS_OFFLINE);

        $this->rideRepo->method('find')->willReturn($ride);
        $this->driverRepo->method('find')->willReturn($driver);

        $result = $this->service->acceptRide(1, 1);

        $this->assertNull($result);
    }

    public function testDriverArrivedReturnsRideWhenSuccessful(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ACCEPTED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->driverArrived(1);

        $this->assertNotNull($result);
        $this->assertEquals(Ride::STATUS_ARRIVED, $result->getStatus());
        $this->assertInstanceOf(\DateTime::class, $result->getArrivedAt());
    }

    public function testDriverArrivedReturnsNullWhenRideNotAccepted(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_REQUESTED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->driverArrived(1);

        $this->assertNull($result);
    }

    public function testStartRideReturnsRideWhenSuccessful(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ARRIVED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->startRide(1);

        $this->assertNotNull($result);
        $this->assertEquals(Ride::STATUS_IN_PROGRESS, $result->getStatus());
        $this->assertInstanceOf(\DateTime::class, $result->getStartedAt());
    }

    public function testStartRideReturnsNullWhenRideNotArrived(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ACCEPTED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->startRide(1);

        $this->assertNull($result);
    }

    public function testCompleteRideReturnsRideWhenSuccessful(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-123');
        $driver->setStatus(Driver::STATUS_BUSY);

        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $ride->setDriver($driver);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->completeRide(1, 50000.00, 1200, 5000);

        $this->assertNotNull($result);
        $this->assertEquals(Ride::STATUS_COMPLETED, $result->getStatus());
        $this->assertEquals(50000.00, $result->getActualPrice());
        $this->assertEquals(5000, $result->getDistance());
        $this->assertInstanceOf(Payment::class, $result->getPayment());
        $this->assertEquals(Driver::STATUS_ONLINE, $driver->getStatus());
    }

    public function testCompleteRideReturnsNullWhenRideNotInProgress(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ARRIVED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->completeRide(1, 50000.00, 1200, 5000);

        $this->assertNull($result);
    }

    public function testCancelRideReturnsRideWhenSuccessful(): void
    {
        $driver = new Driver();
        $driver->setStatus(Driver::STATUS_BUSY);

        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_REQUESTED);
        $ride->setDriver($driver);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->cancelRide(1, 'Changed my mind');

        $this->assertNotNull($result);
        $this->assertEquals(Ride::STATUS_CANCELLED, $result->getStatus());
    }

    public function testCancelRideReturnsNullWhenRideNotActive(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_COMPLETED);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->cancelRide(1, 'Changed my mind');

        $this->assertNull($result);
    }

    public function testFindNearbyDrivers(): void
    {
        $drivers = [new Driver(), new Driver()];
        $this->driverRepo->method('findAvailableDrivers')->willReturn($drivers);

        $result = $this->service->findNearbyDrivers(10.8231, 106.6297, 5.0, 'car');

        $this->assertCount(2, $result);
    }

    public function testRateRideReturnsRideWhenSuccessful(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-123');
        $driver->setRating(4.5);

        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $ride->setDriver($driver);

        $this->rideRepo->method('find')->willReturn($ride);
        $this->rideRepo->method('findBy')->willReturn([$ride]);

        $result = $this->service->rateRide(1, 4.5, 'Great driver!');

        $this->assertNotNull($result);
        $this->assertEquals(4.5, $result->getDriverRating());
        $this->assertEquals('Great driver!', $result->getDriverReview());
    }

    public function testRateRideReturnsNullWhenRideNotCompleted(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);

        $this->rideRepo->method('find')->willReturn($ride);

        $result = $this->service->rateRide(1, 4.5, 'Great driver!');

        $this->assertNull($result);
    }

    public function testGetRideHistory(): void
    {
        $rides = [new Ride(), new Ride()];
        $this->rideRepo->method('findCompletedRidesByUser')->willReturn($rides);

        $result = $this->service->getRideHistory('user-123');

        $this->assertCount(2, $result);
    }

    public function testGetActiveRide(): void
    {
        $ride = new Ride();
        $this->rideRepo->method('findActiveRidesByRider')->willReturn([$ride]);

        $result = $this->service->getActiveRide('user-123');

        $this->assertNotNull($result);
    }

    public function testGetActiveRideReturnsNullWhenNoActiveRide(): void
    {
        $this->rideRepo->method('findActiveRidesByRider')->willReturn([]);

        $result = $this->service->getActiveRide('user-123');

        $this->assertNull($result);
    }

    public function testUpdateDriverLocation(): void
    {
        $driver = new Driver();
        $this->driverRepo->method('find')->willReturn($driver);

        $this->service->updateDriverLocation(1, 10.8231, 106.6297);

        $this->assertEquals(10.8231, $driver->getCurrentLatitude());
        $this->assertEquals(106.6297, $driver->getCurrentLongitude());
        $this->assertInstanceOf(\DateTime::class, $driver->getLastActiveAt());
    }
}