<?php

declare(strict_types=1);

namespace Tests\Unit\RideHailing\Controller;

use App\RideHailing\Controller\RideHailingController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use InvalidArgumentException;

class RideHailingControllerValidationTest extends TestCase
{
    private RideHailingController $controller;

    protected function setUp(): void
    {
        // Instantiate without constructor to avoid heavy dependencies
        $reflection = new \ReflectionClass(RideHailingController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->controller, ...$args);
    }

    // ==================== validateRequestRideData ====================

    public function testValidateRequestRideData_AllValid_NoException(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => '123 Main St',
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->invokePrivate('validateRequestRideData', [$data]);
        $this->assertTrue(true); // no exception
    }

    public function testValidateRequestRideData_MissingPickupLatitude_Throws(): void
    {
        $data = [
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: pickupLatitude');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_MissingPickupLongitude_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: pickupLongitude');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_MissingPickupAddress_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: pickupAddress');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_MissingEstimatedPrice_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: estimatedPrice');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_MissingEstimatedDuration_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => 50000,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field: estimatedDuration');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_InvalidCoordinate_Throws(): void
    {
        $data = [
            'pickupLatitude' => 200, // invalid
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude (must be between -90 and 90)');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_PickupAddressTooLong_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => str_repeat('a', 501),
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pickup address too long');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_DropoffAddressTooLong_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'dropoffAddress' => str_repeat('b', 501),
            'estimatedPrice' => 50000,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dropoff address too long');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_NegativePrice_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => -100,
            'estimatedDuration' => 30,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price and duration must be non-negative');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    public function testValidateRequestRideData_NegativeDuration_Throws(): void
    {
        $data = [
            'pickupLatitude' => 10.0,
            'pickupLongitude' => 106.0,
            'pickupAddress' => 'Addr',
            'estimatedPrice' => 50000,
            'estimatedDuration' => -1,
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price and duration must be non-negative');
        $this->invokePrivate('validateRequestRideData', [$data]);
    }

    // ==================== validateRateRideData ====================

    public function testValidateRateRideData_DriverRatingMissing_Throws(): void
    {
        $data = [];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing driverRating');
        $this->invokePrivate('validateRateRideData', [$data]);
    }

    public function testValidateRateRideData_DriverRatingInvalid_Throws(): void
    {
        $data = ['driverRating' => 6]; // >5
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        $this->invokePrivate('validateRateRideData', [$data]);
    }

    public function testValidateRateRideData_RiderRatingInvalid_Throws(): void
    {
        $data = ['driverRating' => 4, 'riderRating' => 0]; // <1
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        $this->invokePrivate('validateRateRideData', [$data]);
    }

    public function testValidateRateRideData_ReviewTooLong_Throws(): void
    {
        $data = [
            'driverRating' => 4,
            'review' => str_repeat('a', 1001),
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Review too long');
        $this->invokePrivate('validateRateRideData', [$data]);
    }

    public function testValidateRateRideData_Valid_NoException(): void
    {
        $data = [
            'driverRating' => 4,
            'riderRating' => 5,
            'review' => 'Great ride!',
        ];
        $this->invokePrivate('validateRateRideData', [$data]);
        $this->assertTrue(true);
    }
}
