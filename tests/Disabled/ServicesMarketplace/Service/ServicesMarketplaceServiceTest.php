<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Service;

use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Category;
use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\ServiceProvider;
use App\ServicesMarketplace\Entity\Review;
use App\ServicesMarketplace\Repository\BookingRepository;
use App\ServicesMarketplace\Repository\CategoryRepository;
use App\ServicesMarketplace\Repository\ServiceProviderRepository;
use App\ServicesMarketplace\Repository\ServiceRepository;
use App\ServicesMarketplace\Repository\ReviewRepository;
use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use PHPUnit\Framework\TestCase;

class ServicesMarketplaceServiceTest extends TestCase
{
    private ServicesMarketplaceService $service;
    private ServiceProviderRepository $providerRepo;
    private ServiceRepository $serviceRepo;
    private BookingRepository $bookingRepo;
    private ReviewRepository $reviewRepo;
    private CategoryRepository $categoryRepo;

    protected function setUp(): void
    {
        $this->providerRepo = $this->createMock(ServiceProviderRepository::class);
        $this->serviceRepo = $this->createMock(ServiceRepository::class);
        $this->bookingRepo = $this->createMock(BookingRepository::class);
        $this->reviewRepo = $this->createMock(ReviewRepository::class);
        $this->categoryRepo = $this->createMock(CategoryRepository::class);

        $this->service = new ServicesMarketplaceService(
            $this->providerRepo,
            $this->serviceRepo,
            $this->bookingRepo,
            $this->reviewRepo,
            $this->categoryRepo
        );
    }

    public function testRegisterProvider(): void
    {
        $data = [
            'userId' => 'user-123',
            'businessName' => 'Clean Home Services',
            'displayName' => 'Clean Home',
            'description' => 'Professional cleaning',
            'category' => 'cleaning',
            'address' => '123 Main St',
            'latitude' => 10.8231,
            'longitude' => 106.6297,
            'phone' => '+84912345678',
            'startingPrice' => 200000.00,
        ];

        $provider = $this->service->registerProvider($data);

        $this->assertInstanceOf(ServiceProvider::class, $provider);
        $this->assertEquals('user-123', $provider->getUserId());
        $this->assertEquals('Clean Home Services', $provider->getBusinessName());
        $this->assertEquals('cleaning', $provider->getCategory());
        $this->assertEquals('pending', $provider->getStatus());
    }

    public function testGetNearbyProviders(): void
    {
        $providers = [new ServiceProvider(), new ServiceProvider()];
        $this->providerRepo->method('findNearby')->willReturn($providers);

        $result = $this->service->getNearbyProviders(10.8231, 106.6297, 10, 20);

        $this->assertCount(2, $result);
    }

    public function testGetProvidersByCategory(): void
    {
        $providers = [new ServiceProvider()];
        $this->providerRepo->method('findByCategory')->willReturn($providers);

        $result = $this->service->getProvidersByCategory('cleaning');

        $this->assertCount(1, $result);
    }

    public function testSearchProviders(): void
    {
        $providers = [new ServiceProvider()];
        $this->providerRepo->method('searchByName')->willReturn($providers);

        $result = $this->service->searchProviders('clean');

        $this->assertCount(1, $result);
    }

    public function testGetProvider(): void
    {
        $provider = new ServiceProvider();
        $this->providerRepo->method('find')->willReturn($provider);

        $result = $this->service->getProvider(1);

        $this->assertNotNull($result);
    }

    public function testGetProviderReturnsNullWhenNotFound(): void
    {
        $this->providerRepo->method('find')->willReturn(null);

        $result = $this->service->getProvider(999);

        $this->assertNull($result);
    }

    public function testApproveProvider(): void
    {
        $provider = new ServiceProvider();
        $provider->setStatus('pending');

        $this->providerRepo->method('find')->willReturn($provider);

        $result = $this->service->approveProvider(1);

        $this->assertNotNull($result);
        $this->assertEquals('approved', $result->getStatus());
    }

    public function testApproveProviderReturnsNullWhenNotFound(): void
    {
        $this->providerRepo->method('find')->willReturn(null);

        $result = $this->service->approveProvider(999);

        $this->assertNull($result);
    }

    public function testCreateBooking(): void
    {
        $provider = new ServiceProvider();
        $provider->setStatus('approved');

        $service = new Service();
        $service->setPrice(500000.00);
        $service->setEstimatedDuration(60);

        $this->providerRepo->method('find')->willReturn($provider);
        $this->serviceRepo->method('find')->willReturn($service);

        $data = [
            'userId' => 'user-123',
            'providerId' => 1,
            'serviceId' => 1,
            'address' => '456 Customer St',
            'latitude' => 10.8,
            'longitude' => 106.6,
            'scheduledAt' => '+1 day',
        ];

        $booking = $this->service->createBooking($data);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('user-123', $booking->getUserId());
        $this->assertEquals(Booking::STATUS_PENDING, $booking->getStatus());
    }

    public function testCreateBookingThrowsExceptionWhenProviderNotApproved(): void
    {
        $provider = new ServiceProvider();
        $provider->setStatus('pending');

        $this->providerRepo->method('find')->willReturn($provider);

        $data = [
            'userId' => 'user-123',
            'providerId' => 1,
            'serviceId' => 1,
            'scheduledAt' => '+1 day',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Provider not available');
        $this->service->createBooking($data);
    }

    public function testCreateBookingThrowsExceptionWhenServiceNotFound(): void
    {
        $provider = new ServiceProvider();
        $provider->setStatus('approved');

        $this->providerRepo->method('find')->willReturn($provider);
        $this->serviceRepo->method('find')->willReturn(null);

        $data = [
            'userId' => 'user-123',
            'providerId' => 1,
            'serviceId' => 999,
            'scheduledAt' => '+1 day',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found');
        $this->service->createBooking($data);
    }

    public function testConfirmBooking(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_PENDING);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->confirmBooking(1);

        $this->assertNotNull($result);
        $this->assertEquals(Booking::STATUS_CONFIRMED, $result->getStatus());
    }

    public function testConfirmBookingReturnsNullWhenNotPending(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_COMPLETED);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->confirmBooking(1);

        $this->assertNull($result);
    }

    public function testStartBooking(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_CONFIRMED);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->startBooking(1);

        $this->assertNotNull($result);
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $result->getStatus());
    }

    public function testStartBookingReturnsNullWhenNotConfirmed(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_PENDING);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->startBooking(1);

        $this->assertNull($result);
    }

    public function testCompleteBooking(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompletedJobs(0);

        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_IN_PROGRESS);
        $booking->setProvider($provider);
        $booking->setProviderEarning(400000.00);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->completeBooking(1);

        $this->assertNotNull($result);
        $this->assertEquals(Booking::STATUS_COMPLETED, $result->getStatus());
        $this->assertEquals(1, $provider->getCompletedJobs());
    }

    public function testCancelBooking(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_PENDING);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->cancelBooking(1, 'Customer cancelled');

        $this->assertNotNull($result);
        $this->assertEquals(Booking::STATUS_CANCELLED, $result->getStatus());
    }

    public function testCancelBookingReturnsNullWhenCannotCancel(): void
    {
        $booking = new Booking();
        $booking->setStatus(Booking::STATUS_IN_PROGRESS);

        $this->bookingRepo->method('find')->willReturn($booking);

        $result = $this->service->cancelBooking(1, 'Customer cancelled');

        $this->assertNull($result);
    }

    public function testGetUserBookings(): void
    {
        $bookings = [new Booking(), new Booking()];
        $this->bookingRepo->method('findByUserId')->willReturn($bookings);

        $result = $this->service->getUserBookings('user-123');

        $this->assertCount(2, $result);
    }

    public function testGetProviderBookings(): void
    {
        $bookings = [new Booking()];
        $this->bookingRepo->method('findByProvider')->willReturn($bookings);

        $result = $this->service->getProviderBookings(1);

        $this->assertCount(1, $result);
    }

    public function testAddReview(): void
    {
        $provider = new ServiceProvider();
        $provider->setId(1);

        $booking = new Booking();
        $booking->setProvider($provider);

        $this->bookingRepo->method('find')->willReturn($booking);
        $this->reviewRepo->method('findByProvider')->willReturn([]);

        $data = [
            'bookingId' => 1,
            'userId' => 'user-123',
            'rating' => 5.0,
            'comment' => 'Excellent service!',
        ];

        $review = $this->service->addReview($data);

        $this->assertInstanceOf(Review::class, $review);
        $this->assertEquals(5.0, $review->getRating());
    }

    public function testAddReviewThrowsExceptionWhenBookingNotFound(): void
    {
        $this->bookingRepo->method('find')->willReturn(null);

        $data = [
            'bookingId' => 999,
            'userId' => 'user-123',
            'rating' => 5.0,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking not found');
        $this->service->addReview($data);
    }

    public function testGetCategories(): void
    {
        $categories = [new Category(), new Category()];
        $this->categoryRepo->method('findActiveCategories')->willReturn($categories);

        $result = $this->service->getCategories();

        $this->assertCount(2, $result);
    }

    public function testCreateCategory(): void
    {
        $data = [
            'name' => 'Home Cleaning',
            'description' => 'Professional home cleaning services',
            'iconUrl' => 'https://example.com/cleaning.png',
            'displayOrder' => 1,
        ];

        $category = $this->service->createCategory($data);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Home Cleaning', $category->getName());
        $this->assertEquals('home-cleaning', $category->getSlug());
    }

    public function testGetProviderStats(): void
    {
        $completedBooking = new Booking();
        $completedBooking->setStatus(Booking::STATUS_COMPLETED);
        $completedBooking->setProviderEarning(500000.00);

        $pendingBooking = new Booking();
        $pendingBooking->setStatus(Booking::STATUS_PENDING);

        $this->bookingRepo->method('findByProvider')->willReturn([$completedBooking, $pendingBooking]);

        $result = $this->service->getProviderStats(1);

        $this->assertEquals(2, $result['total_bookings']);
        $this->assertEquals(1, $result['completed_bookings']);
        $this->assertEquals(500000.00, $result['total_earnings']);
        $this->assertEquals(1, $result['pending_bookings']);
    }
}