<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Controller;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Payment;
use App\RideHailing\Service\RideHailingService;
use App\RideHailing\Service\DriverService;
use App\RideHailing\Controller\RideHailingController;
use PHPUnit\Framework\TestCase;

class RideHailingControllerTest extends TestCase
{
    private RideHailingController $controller;
    private RideHailingService $rideService;
    private DriverService $driverService;

    protected function setUp(): void
    {
        $this->rideService = $this->createMock(RideHailingService::class);
        $this->driverService = $this->createMock(DriverService::class);
        $this->controller = new RideHailingController($this->rideService, $this->driverService);
    }

    public function testRequestRideReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-123');

        $this->rideService->method('requestRide')->willReturn($ride);

        $request = [
            'body' => json_encode([
                'riderId' => 'rider-123',
                'pickupLatitude' => 10.8231,
                'pickupLongitude' => 106.6297,
                'pickupAddress' => '123 Nguyen Hue',
                'estimatedPrice' => 50000,
                'estimatedDuration' => 1800,
            ]),
        ];

        $response = $this->controller->requestRide($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testRequestRideReturnsErrorForInvalidJson(): void
    {
        $request = ['body' => 'invalid json'];

        $response = $this->controller->requestRide($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testRequestRideReturnsErrorForMissingFields(): void
    {
        $request = ['body' => json_encode(['riderId' => 'rider-123'])];

        $response = $this->controller->requestRide($request);

        $this->assertEquals(400, $response['error']);
        $this->assertStringContainsString('Missing field', $response['error']);
    }

    public function testAcceptRideReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ACCEPTED);

        $this->rideService->method('acceptRide')->willReturn($ride);

        $request = ['body' => json_encode(['driverId' => 1])];

        $response = $this->controller->acceptRide($request, 1);

        $this->assertEquals(200, $response['success']);
    }

    public function testAcceptRideReturnsErrorWhenUnableToAccept(): void
    {
        $this->rideService->method('acceptRide')->willReturn(null);

        $request = ['body' => json_encode(['driverId' => 1])];

        $response = $this->controller->acceptRide($request, 1);

        $this->assertEquals(400, $response['error']);
    }

    public function testDriverArrivedReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_ARRIVED);

        $this->rideService->method('driverArrived')->willReturn($ride);

        $response = $this->controller->driverArrived(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testDriverArrivedReturnsErrorWhenNotFound(): void
    {
        $this->rideService->method('driverArrived')->willReturn(null);

        $response = $this->controller->driverArrived(999);

        $this->assertEquals(400, $response['error']);
    }

    public function testStartRideReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);

        $this->rideService->method('startRide')->willReturn($ride);

        $response = $this->controller->startRide(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testStartRideReturnsErrorWhenNotFound(): void
    {
        $this->rideService->method('startRide')->willReturn(null);

        $response = $this->controller->startRide(999);

        $this->assertEquals(400, $response['error']);
    }

    public function testCompleteRideReturnsSuccess(): void
    {
        $payment = new Payment();
        $payment->setTransactionId('TXN-123');

        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $ride->setPayment($payment);

        $this->rideService->method('completeRide')->willReturn($ride);

        $request = ['body' => json_encode([
            'actualPrice' => 50000,
            'actualDuration' => 1200,
            'distance' => 5000,
        ])];

        $response = $this->controller->completeRide($request, 1);

        $this->assertEquals(200, $response['success']);
    }

    public function testCompleteRideReturnsErrorForMissingFields(): void
    {
        $request = ['body' => json_encode(['actualPrice' => 50000])];

        $response = $this->controller->completeRide($request, 1);

        $this->assertEquals(400, $response['error']);
    }

    public function testCancelRideReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_CANCELLED);

        $this->rideService->method('cancelRide')->willReturn($ride);

        $request = ['body' => json_encode(['reason' => 'Changed my mind'])];

        $response = $this->controller->cancelRide($request, 1);

        $this->assertEquals(200, $response['success']);
    }

    public function testCancelRideReturnsErrorWhenNotFound(): void
    {
        $this->rideService->method('cancelRide')->willReturn(null);

        $request = ['body' => json_encode(['reason' => 'Changed my mind'])];

        $response = $this->controller->cancelRide($request, 999);

        $this->assertEquals(400, $response['error']);
    }

    public function testRateRideReturnsSuccess(): void
    {
        $ride = new Ride();
        $ride->setStatus(Ride::STATUS_COMPLETED);

        $this->rideService->method('rateRide')->willReturn($ride);

        $request = ['body' => json_encode(['driverRating' => 4.5])];

        $response = $this->controller->rateRide($request, 1);

        $this->assertEquals(200, $response['success']);
    }

    public function testRateRideReturnsErrorForMissingRating(): void
    {
        $request = ['body' => json_encode(['review' => 'Great!'])];

        $response = $this->controller->rateRide($request, 1);

        $this->assertEquals(400, $response['error']);
    }

    public function testNearbyDriversReturnsSuccess(): void
    {
        $drivers = [new Driver(), new Driver()];
        $this->rideService->method('findNearbyDrivers')->willReturn($drivers);

        $request = ['query' => [
            'latitude' => 10.8231,
            'longitude' => 106.6297,
            'radius' => 5.0,
        ]];

        $response = $this->controller->nearbyDrivers($request);

        $this->assertEquals(200, $response['success']);
        $this->assertCount(2, $response['drivers']);
    }

    public function testNearbyDriversReturnsErrorForMissingCoordinates(): void
    {
        $request = ['query' => []];

        $response = $this->controller->nearbyDrivers($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testGetRideHistoryReturnsSuccess(): void
    {
        $rides = [new Ride(), new Ride()];
        $this->rideService->method('getRideHistory')->willReturn($rides);

        $response = $this->controller->getRideHistory('rider-123');

        $this->assertEquals(200, $response['success']);
        $this->assertCount(2, $response['rides']);
    }

    public function testGetActiveRideReturnsRide(): void
    {
        $ride = new Ride();
        $this->rideService->method('getActiveRide')->willReturn($ride);

        $response = $this->controller->getActiveRide('rider-123');

        $this->assertEquals(200, $response['success']);
        $this->assertNotNull($response['ride']);
    }

    public function testGetActiveRideReturnsNullWhenNoActiveRide(): void
    {
        $this->rideService->method('getActiveRide')->willReturn(null);

        $response = $this->controller->getActiveRide('rider-123');

        $this->assertEquals(200, $response['success']);
        $this->assertNull($response['ride']);
    }

    public function testUpdateDriverLocationReturnsSuccess(): void
    {
        $request = ['body' => json_encode([
            'driverId' => 1,
            'latitude' => 10.8231,
            'longitude' => 106.6297,
        ])];

        $response = $this->controller->updateDriverLocation($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testUpdateDriverLocationReturnsErrorForMissingFields(): void
    {
        $request = ['body' => json_encode(['driverId' => 1])];

        $response = $this->controller->updateDriverLocation($request);

        $this->assertEquals(400, $response['error']);
    }
}