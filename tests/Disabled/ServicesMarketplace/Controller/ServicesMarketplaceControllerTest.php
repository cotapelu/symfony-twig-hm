<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Controller;

use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Category;
use App\ServicesMarketplace\Entity\ServiceProvider;
use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use App\ServicesMarketplace\Controller\ServicesMarketplaceController;
use PHPUnit\Framework\TestCase;

class ServicesMarketplaceControllerTest extends TestCase
{
    private ServicesMarketplaceController $controller;
    private ServicesMarketplaceService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ServicesMarketplaceService::class);
        $this->controller = new ServicesMarketplaceController($this->service);
    }

    public function testGetCategoriesReturnsSuccess(): void
    {
        $categories = [new Category(), new Category()];
        $this->service->method('getCategories')->willReturn($categories);

        $response = $this->controller->getCategories();

        $this->assertEquals(200, $response['success']);
        $this->assertCount(2, $response['categories']);
    }

    public function testGetNearbyProvidersReturnsSuccess(): void
    {
        $providers = [new ServiceProvider(), new ServiceProvider()];
        $this->service->method('getNearbyProviders')->willReturn($providers);

        $request = ['query' => ['latitude' => 10.8231, 'longitude' => 106.6297]];

        $response = $this->controller->getNearbyProviders($request);

        $this->assertEquals(200, $response['success']);
        $this->assertCount(2, $response['providers']);
    }

    public function testGetNearbyProvidersReturnsErrorForMissingCoordinates(): void
    {
        $request = ['query' => []];

        $response = $this->controller->getNearbyProviders($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testGetProvidersByCategoryReturnsSuccess(): void
    {
        $providers = [new ServiceProvider()];
        $this->service->method('getProvidersByCategory')->willReturn($providers);

        $response = $this->controller->getProvidersByCategory('cleaning');

        $this->assertEquals(200, $response['success']);
        $this->assertCount(1, $response['providers']);
    }

    public function testSearchProvidersReturnsSuccess(): void
    {
        $providers = [new ServiceProvider()];
        $this->service->method('searchProviders')->willReturn($providers);

        $request = ['query' => ['q' => 'clean']];

        $response = $this->controller->searchProviders($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testSearchProvidersReturnsErrorForShortQuery(): void
    {
        $request = ['query' => ['q' => 'a']];

        $response = $this->controller->searchProviders($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testGetProviderReturnsSuccess(): void
    {
        $provider = new ServiceProvider();
        $this->service->method('getProvider')->willReturn($provider);

        $response = $this->controller->getProvider(1);

        $this->assertEquals(200, $response['success']);
        $this->assertNotNull($response['provider']);
    }

    public function testGetProviderReturnsErrorWhenNotFound(): void
    {
        $this->service->method('getProvider')->willReturn(null);

        $response = $this->controller->getProvider(999);

        $this->assertEquals(404, $response['error']);
    }

    public function testRegisterProviderReturnsSuccess(): void
    {
        $provider = new ServiceProvider();
        $this->service->method('registerProvider')->willReturn($provider);

        $request = ['body' => json_encode([
            'userId' => 'user-123',
            'businessName' => 'Clean Home',
            'category' => 'cleaning',
        ])];

        $response = $this->controller->registerProvider($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testRegisterProviderReturnsErrorForInvalidJson(): void
    {
        $request = ['body' => 'invalid json'];

        $response = $this->controller->registerProvider($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testCreateBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setUserId('user-123');

        $this->service->method('createBooking')->willReturn($booking);

        $request = ['body' => json_encode([
            'userId' => 'user-123',
            'providerId' => 1,
            'serviceId' => 1,
        ])];

        $response = $this->controller->createBooking($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testCreateBookingReturnsErrorForInvalidJson(): void
    {
        $request = ['body' => 'invalid json'];

        $response = $this->controller->createBooking($request);

        $this->assertEquals(400, $response['error']);
    }

    public function testGetBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setUserId('user-123');

        $this->service->method('getUserBookings')->willReturn([$booking]);

        $response = $this->controller->getBooking(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testGetBookingReturnsErrorWhenNotFound(): void
    {
        $this->service->method('getUserBookings')->willReturn([]);

        $response = $this->controller->getBooking(999);

        $this->assertEquals(404, $response['error']);
    }

    public function testConfirmBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_CONFIRMED);

        $this->service->method('confirmBooking')->willReturn($booking);

        $response = $this->controller->confirmBooking(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testConfirmBookingReturnsErrorWhenCannotConfirm(): void
    {
        $this->service->method('confirmBooking')->willReturn(null);

        $response = $this->controller->confirmBooking(999);

        $this->assertEquals(400, $response['error']);
    }

    public function testStartBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_IN_PROGRESS);

        $this->service->method('startBooking')->willReturn($booking);

        $response = $this->controller->startBooking(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testStartBookingReturnsErrorWhenCannotStart(): void
    {
        $this->service->method('startBooking')->willReturn(null);

        $response = $this->controller->startBooking(999);

        $this->assertEquals(400, $response['error']);
    }

    public function testCompleteBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_COMPLETED);

        $this->service->method('completeBooking')->willReturn($booking);

        $response = $this->controller->completeBooking(1);

        $this->assertEquals(200, $response['success']);
    }

    public function testCompleteBookingReturnsErrorWhenCannotComplete(): void
    {
        $this->service->method('completeBooking')->willReturn(null);

        $response = $this->controller->completeBooking(999);

        $this->assertEquals(400, $response['error']);
    }

    public function testCancelBookingReturnsSuccess(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_CANCELLED);

        $this->service->method('cancelBooking')->willReturn($booking);

        $request = ['body' => json_encode(['reason' => 'Changed my mind'])];

        $response = $this->controller->cancelBooking($request, 1);

        $this->assertEquals(200, $response['success']);
    }

    public function testCancelBookingReturnsErrorWhenCannotCancel(): void
    {
        $this->service->method('cancelBooking')->willReturn(null);

        $request = ['body' => json_encode(['reason' => 'Changed my mind'])];

        $response = $this->controller->cancelBooking($request, 999);

        $this->assertEquals(400, $response['error']);
    }

    public function testGetCustomerBookingsReturnsSuccess(): void
    {
        $bookings = [new Booking(), new Booking()];
        $this->service->method('getUserBookings')->willReturn($bookings);

        $response = $this->controller->getCustomerBookings('user-123');

        $this->assertEquals(200, $response['success']);
        $this->assertCount(2, $response['bookings']);
    }

    public function testGetProviderBookingsReturnsSuccess(): void
    {
        $bookings = [new Booking()];
        $this->service->method('getProviderBookings')->willReturn($bookings);

        $response = $this->controller->getProviderBookings(1);

        $this->assertEquals(200, $response['success']);
        $this->assertCount(1, $response['bookings']);
    }

    public function testGetProviderStatsReturnsSuccess(): void
    {
        $stats = [
            'total_bookings' => 10,
            'completed_bookings' => 8,
            'total_earnings' => 4000000,
            'pending_bookings' => 2,
        ];
        $this->service->method('getProviderStats')->willReturn($stats);

        $response = $this->controller->getProviderStats(1);

        $this->assertEquals(200, $response['success']);
        $this->assertEquals(10, $response['stats']['total_bookings']);
    }

    public function testAddReviewReturnsSuccess(): void
    {
        $this->service->method('addReview')->willReturn(new \App\ServicesMarketplace\Entity\Review());

        $request = ['body' => json_encode([
            'bookingId' => 1,
            'userId' => 'user-123',
            'rating' => 5.0,
        ])];

        $response = $this->controller->addReview($request);

        $this->assertEquals(200, $response['success']);
    }

    public function testAddReviewReturnsErrorForInvalidJson(): void
    {
        $request = ['body' => 'invalid json'];

        $response = $this->controller->addReview($request);

        $this->assertEquals(400, $response['error']);
    }
}