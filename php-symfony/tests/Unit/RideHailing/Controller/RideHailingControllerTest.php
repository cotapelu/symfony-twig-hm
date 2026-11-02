<?php

declare(strict_types=1);

namespace Tests\Unit\RideHailing\Controller;

use App\RideHailing\Controller\RideHailingController;
use App\RideHailing\Service\RideHailingService;
use App\RideHailing\Service\DriverService;
use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RideHailingControllerTest extends TestCase
{
    private RideHailingService $rideServiceMock;
    private DriverService $driverServiceMock;
    private RideHailingController $controller;

    protected function setUp(): void
    {
        $this->rideServiceMock = $this->createMock(RideHailingService::class);
        $this->driverServiceMock = $this->createMock(DriverService::class);
        $this->controller = new RideHailingController($this->rideServiceMock, $this->driverServiceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(string $userId = '123'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createRide(int $id, string $riderId, ?Driver $driver = null, string $status = 'requested'): Ride
    {
        $ride = new Ride();
        $ride->setId($id);
        $ride->setRiderId($riderId);
        $ride->setDriver($driver);
        $ride->setStatus($status);
        $ride->setPickupLatitude(21.0);
        $ride->setPickupLongitude(105.0);
        $ride->setPickupAddress('Hanoi');
        $ride->setDropoffLatitude(21.1);
        $ride->setDropoffLongitude(105.1);
        $ride->setDropoffAddress('Da Nang');
        $ride->setRideType('standard');
        $ride->setEstimatedPrice(10000);
        $ride->setEstimatedDuration(25);
        $ride->setDistance(10);
        $ride->setDriverRating(null);
        return $ride;
    }

    private function createDriver(int $id, string $userId): Driver
    {
        $driver = new Driver();
        $driver->setId($id);
        $driver->setUserId($userId);
        $driver->setStatus(Driver::STATUS_ONLINE);
        $driver->setActive(true);
        $driver->setCurrentLatitude(21.0);
        $driver->setCurrentLongitude(105.0);
        $driver->setRating(5.0);
        $driver->setVehicleType('car');
        $driver->setVehicleColor('white');
        return $driver;
    }

    // ==================== Validation helpers ====================

    public function testValidateCoordinate_Valid(): void
    {
        $this->assertNull($this->invokePrivate($this->controller, 'validateCoordinate', [21.0, 105.0]));
    }

    public function testValidateCoordinate_InvalidLat(): void
    {
        $this->assertSame('Invalid latitude (must be between -90 and 90)', $this->invokePrivate($this->controller, 'validateCoordinate', [91.0, 105.0]));
    }

    public function testValidateCoordinate_InvalidLng(): void
    {
        $this->assertSame('Invalid longitude (must be between -180 and 180)', $this->invokePrivate($this->controller, 'validateCoordinate', [21.0, 181.0]));
    }

    public function testValidateRating_Valid(): void
    {
        $this->assertNull($this->invokePrivate($this->controller, 'validateRating', [3.5]));
    }

    public function testValidateRating_TooLow(): void
    {
        $this->assertSame('Rating must be between 1 and 5', $this->invokePrivate($this->controller, 'validateRating', [0.5]));
    }

    public function testValidateRating_TooHigh(): void
    {
        $this->assertSame('Rating must be between 1 and 5', $this->invokePrivate($this->controller, 'validateRating', [5.5]));
    }

    public function testSanitizeString_NullEmpty(): void
    {
        $this->assertSame('', $this->invokePrivate($this->controller, 'sanitizeString', [null]));
    }

    public function testSanitizeString_Escapes(): void
    {
        $input = '<script>alert("x")</script>';
        $result = $this->invokePrivate($this->controller, 'sanitizeString', [$input]);
        $this->assertStringNotContainsString('<script>', $result);
    }

    // ==================== requestRide ====================

    public function testRequestRide_Unauthenticated_Returns403(): void
    {
        $request = Request::create('/api/ride-hailing/rides', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'pickupLatitude' => 21.0, 'pickupLongitude' => 105.0, 'pickupAddress' => 'Hanoi', 'estimatedPrice' => 10000, 'estimatedDuration' => 25
        ]));
        $response = $this->controller->requestRide($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRequestRide_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('rider1');
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $response = $this->controller->requestRide($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRequestRide_MissingFields_Returns400(): void
    {
        $this->setAuthenticatedUser('rider1');
        $request = Request::create('/api/ride-hailing/rides', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'pickupLatitude' => 21.0, 'pickupLongitude' => 105.0 // missing pickupAddress, estimatedPrice, estimatedDuration
        ]));
        $response = $this->controller->requestRide($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRequestRide_InvalidCoords_Returns400(): void
    {
        $this->setAuthenticatedUser('rider1');
        $request = Request::create('/api/ride-hailing/rides', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'pickupLatitude' => 100.0, 'pickupLongitude' => 105.0, 'pickupAddress' => 'Hanoi', 'estimatedPrice' => 10000, 'estimatedDuration' => 25
        ]));
        $response = $this->controller->requestRide($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRequestRide_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('rider1');
        $ride = $this->createRide(1, 'rider1');
        $this->rideServiceMock->expects($this->once())
            ->method('requestRide')
            ->with($this->callback(function($data) {
                return $data['pickupLatitude'] == 21.0
                    && $data['pickupLongitude'] == 105.0
                    && $data['pickupAddress'] === 'Hanoi'
                    && $data['estimatedPrice'] == 10000
                    && $data['estimatedDuration'] == 25
                    && $data['riderId'] === 'rider1';
            }))
            ->willReturn($ride);
        $request = Request::create('/api/ride-hailing/rides', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'pickupLatitude' => 21.0, 'pickupLongitude' => 105.0, 'pickupAddress' => 'Hanoi', 'estimatedPrice' => 10000, 'estimatedDuration' => 25
        ]));
        $response = $this->controller->requestRide($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('ride', $content);
    }

    // ==================== acceptRide ====================

    public function testAcceptRide_Unauthenticated_Returns403(): void
    {
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->willReturn($driver);
        $response = $this->controller->acceptRide(new Request(), 1);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAcceptRide_NoDriver_Returns404(): void
    {
        $this->setAuthenticatedUser('driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn(null);
        $response = $this->controller->acceptRide(new Request(), 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAcceptRide_RideNotFound_Returns400(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $this->rideServiceMock->method('acceptRide')->with(1, 1)->willReturn(null);
        $response = $this->controller->acceptRide(new Request(), 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAcceptRide_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $ride = $this->createRide(1, 'rider1', $driver, 'accepted');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $this->rideServiceMock->expects($this->once())
            ->method('acceptRide')
            ->with(1, 1)
            ->willReturn($ride);
        $response = $this->controller->acceptRide(new Request(), 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getRideHistory ====================

    public function testGetRideHistory_Unauthenticated_Returns403(): void
    {
        $response = $this->controller->getRideHistory('user1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetRideHistory_Forbidden_Returns403(): void
    {
        $this->setAuthenticatedUser('current_user');
        $response = $this->controller->getRideHistory('other_user');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetRideHistory_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1');
        $ride = $this->createRide(1, 'user1');
        $this->rideServiceMock->expects($this->once())
            ->method('getRideHistory')
            ->with('user1')
            ->willReturn([$ride]);
        $response = $this->controller->getRideHistory('user1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('rides', $content);
    }

    // ==================== getActiveRide ====================

    public function testGetActiveRide_Unauthenticated_Returns403(): void
    {
        $response = $this->controller->getActiveRide('user1');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetActiveRide_Forbidden_Returns403(): void
    {
        $this->setAuthenticatedUser('current_user');
        $response = $this->controller->getActiveRide('other_user');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetActiveRide_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('user1');
        $ride = $this->createRide(1, 'user1');
        $this->rideServiceMock->expects($this->once())
            ->method('getActiveRide')
            ->with('user1')
            ->willReturn($ride);
        $response = $this->controller->getActiveRide('user1');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('ride', $content);
    }

    // ==================== setDriverOnline ====================

    public function testSetDriverOnline_Unauthenticated_Returns403(): void
    {
        $request = Request::create('/api/ride-hailing/driver/online', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->setDriverOnline($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSetDriverOnline_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->setDriverOnline($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetDriverOnline_MissingFields_Returns400(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $request = Request::create('/api/ride-hailing/driver/online', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0
        ]));
        $response = $this->controller->setDriverOnline($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetDriverOnline_NoDriver_Returns404(): void
    {
        $this->setAuthenticatedUser('driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn(null);
        $request = Request::create('/api/ride-hailing/driver/online', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->setDriverOnline($request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetDriverOnline_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $this->driverServiceMock->expects($this->once())
            ->method('setOnline')
            ->with(1, 21.0, 105.0)
            ->willReturn($driver);
        $request = Request::create('/api/ride-hailing/driver/online', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->setDriverOnline($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== setDriverOffline ====================

    public function testSetDriverOffline_Unauthenticated_Returns403(): void
    {
        $response = $this->controller->setDriverOffline(new Request());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSetDriverOffline_NoDriver_Returns404(): void
    {
        $this->setAuthenticatedUser('driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn(null);
        $response = $this->controller->setDriverOffline(new Request());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetDriverOffline_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $this->driverServiceMock->expects($this->once())
            ->method('setOffline')
            ->with(1)
            ->willReturn($driver);
        $response = $this->controller->setDriverOffline(new Request());
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== updateDriverLocation ====================

    public function testUpdateDriverLocation_Unauthenticated_Returns403(): void
    {
        $request = Request::create('/api/ride-hailing/driver/location', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->updateDriverLocation($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateDriverLocation_MissingFields_Returns400(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $request = Request::create('/api/ride-hailing/driver/location', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0
        ]));
        $response = $this->controller->updateDriverLocation($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateDriverLocation_NoDriver_Returns404(): void
    {
        $this->setAuthenticatedUser('driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn(null);
        $request = Request::create('/api/ride-hailing/driver/location', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->updateDriverLocation($request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateDriverLocation_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('driver1');
        $driver = $this->createDriver(1, 'driver1');
        $this->driverServiceMock->method('getDriverByUserId')->with('driver1')->willReturn($driver);
        $this->rideServiceMock->expects($this->once())
            ->method('updateDriverLocation')
            ->with(1, 21.0, 105.0);
        $request = Request::create('/api/ride-hailing/driver/location', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'latitude' => 21.0, 'longitude' => 105.0
        ]));
        $response = $this->controller->updateDriverLocation($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== nearbyDrivers ====================

    public function testNearbyDrivers_MissingParams_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', ['lat' => 21.0]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNearbyDrivers_InvalidRadius_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $driver = $this->createDriver(1, 'driver1');
        $this->rideServiceMock->expects($this->never())
            ->method('findNearbyDrivers');
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', [
            'latitude' => 21.0,
            'longitude' => 105.0,
            'radius' => 100
        ]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNearbyDrivers_NegativeRadius_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $this->rideServiceMock->expects($this->never())
            ->method('findNearbyDrivers');
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', [
            'latitude' => 21.0,
            'longitude' => 105.0,
            'radius' => -5
        ]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNearbyDrivers_Success_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $driver = $this->createDriver(1, 'driver1');
        $this->rideServiceMock->expects($this->once())
            ->method('findNearbyDrivers')
            ->with(21.0, 105.0, 5.0, null)
            ->willReturn([$driver]);
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', [
            'latitude' => 21.0,
            'longitude' => 105.0
        ]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('drivers', $content);
        $this->assertCount(1, $content['drivers']);
    }

    public function testNearbyDrivers_WithVehicleType_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $driver = $this->createDriver(1, 'driver1');
        $this->rideServiceMock->expects($this->once())
            ->method('findNearbyDrivers')
            ->with(21.0, 105.0, 5.0, 'car')
            ->willReturn([$driver]);
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', [
            'latitude' => 21.0,
            'longitude' => 105.0,
            'radius' => 5,
            'vehicleType' => 'car'
        ]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNearbyDrivers_EmptyResult_Returns200(): void
    {
        $this->setAuthenticatedUser();
        $this->rideServiceMock->expects($this->once())
            ->method('findNearbyDrivers')
            ->willReturn([]);
        $request = Request::create('/api/ride-hailing/drivers/nearby', 'GET', [
            'latitude' => 21.0,
            'longitude' => 105.0
        ]);
        $response = $this->controller->nearbyDrivers($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(0, $content['drivers']);
    }
}