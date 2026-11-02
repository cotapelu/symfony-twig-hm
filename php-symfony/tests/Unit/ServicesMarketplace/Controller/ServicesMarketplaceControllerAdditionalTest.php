<?php declare(strict_types=1);

namespace Tests\Unit\ServicesMarketplace\Controller;

use App\ServicesMarketplace\Controller\ServicesMarketplaceController;
use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class ServicesMarketplaceControllerAdditionalTest extends TestCase
{
    private ServicesMarketplaceService $serviceMock;
    private ServicesMarketplaceController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(ServicesMarketplaceService::class);
        $this->controller = new ServicesMarketplaceController($this->serviceMock);
    }

    private function setAuthenticatedUser(string $userId = '123'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $user->method('getId')->willReturn($userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    // ==================== Booking actions failure (service returns false) ====================

    public function testConfirmBooking_Failure_ServiceReturnsFalse(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('confirmBooking')
            ->with(1)
            ->willReturn(false);
        $response = $this->controller->confirmBooking(1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Cannot confirm booking', $response->getContent());
    }

    public function testStartBooking_Failure_ServiceReturnsFalse(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('startBooking')
            ->with(1)
            ->willReturn(false);
        $response = $this->controller->startBooking(1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Cannot start booking', $response->getContent());
    }

    public function testCompleteBooking_Failure_ServiceReturnsFalse(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->expects($this->once())
            ->method('completeBooking')
            ->with(1)
            ->willReturn(false);
        $response = $this->controller->completeBooking(1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Cannot complete booking', $response->getContent());
    }

    public function testCancelBooking_Failure_ServiceReturnsFalse(): void
    {
        $this->setAuthenticatedUser();
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['reason' => 'No show']));
        $this->serviceMock->expects($this->once())
            ->method('cancelBooking')
            ->with(1, 'No show')
            ->willReturn(false);
        $response = $this->controller->cancelBooking($request, 1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Cannot cancel booking', $response->getContent());
    }

    // ==================== Null service returns (coalesce to empty) ====================

    public function testGetCustomerBookings_ServiceReturnsNull_ReturnsEmptyArray(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getUserBookings')
            ->with('user1')
            ->willReturn(null);
        $response = $this->controller->getCustomerBookings('user1');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['bookings']);
    }

    public function testGetProviderBookings_ServiceReturnsNull_ReturnsEmptyArray(): void
    {
        $this->setAuthenticatedUser('user1');
        $this->serviceMock->expects($this->once())
            ->method('getProviderBookings')
            ->with(1)
            ->willReturn(null);
        $response = $this->controller->getProviderBookings(1);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['bookings']);
    }

    // ==================== Exception handling via handle() -> handleException() ====================

    public function testGetNearbyProviders_ServiceThrowsException_Returns500(): void
    {
        $request = Request::create('/api/services-marketplace/providers/nearby', 'GET', ['latitude' => 21.0, 'longitude' => 105.0]);
        $this->serviceMock->expects($this->once())
            ->method('getNearbyProviders')
            ->with(21.0, 105.0, 10.0)
            ->will($this->throwException(new \Exception('Service failure')));
        $response = $this->controller->getNearbyProviders($request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    // ==================== Service exception in actions with try-catch ====================

    public function testRegisterProvider_ServiceThrowsException_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/services-marketplace/providers/register', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'businessName' => 'Test Business',
            'displayName' => 'Test Display',
            'category' => 'general',
            'address' => 'Hanoi',
            'phone' => '1234567890',
            'startingPrice' => 1000
        ]));
        $this->serviceMock->expects($this->once())
            ->method('registerProvider')
            ->with($this->anything())
            ->will($this->throwException(new \Exception('Registration failed')));
        $response = $this->controller->registerProvider($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Registration failed', json_decode($response->getContent(), true)['error']);
    }

    public function testCreateBooking_ServiceThrowsException_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/services-marketplace/bookings', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'providerId' => 1,
            'address' => 'Addr',
            'scheduledAt' => '2030-01-01T10:00:00',
            'price' => 5000
        ]));
        $this->serviceMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything())
            ->will($this->throwException(new \Exception('Booking creation failed')));
        $response = $this->controller->createBooking($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Booking creation failed', $response->getContent());
    }

    public function testAddReview_ServiceThrowsException_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $request = Request::create('/api/services-marketplace/reviews', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'bookingId' => 1,
            'providerId' => 1,
            'rating' => 5
        ]));
        $this->serviceMock->expects($this->once())
            ->method('addReview')
            ->with($this->anything())
            ->will($this->throwException(new \Exception('Review failed')));
        $response = $this->controller->addReview($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Review failed', $response->getContent());
    }
}
