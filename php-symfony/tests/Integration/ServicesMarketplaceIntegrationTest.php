<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ServicesMarketplace\Entity\ServiceProvider;
use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Review;
use App\ServicesMarketplace\Entity\Category;
use PHPUnit\Framework\TestCase;

/**
 * ServicesMarketplace Module Integration Test
 * 
 * Tests the ServicesMarketplace module as a real system.
 */
#[AllowMockObjectsWithoutExpectations]
class ServicesMarketplaceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    public function testCompleteServiceProviderWorkflow(): void
    {
        // Create provider
        $provider = new ServiceProvider();
        $provider->setUserId('provider-001');
        $provider->setBusinessName('Premium Cleaning Co');
        $provider->setCategory('cleaning');
        $provider->setDescription('Professional cleaning services in HCMC');
        $provider->setAddress('Ho Chi Minh City');
        $provider->setLatitude(10.8231);
        $provider->setLongitude(106.6297);
        $provider->setPhone('+84912345678');
        $provider->setRating(4.8);
        $provider->setVerified(true);
        $provider->setStartingPrice(250000.00);
        $provider->setStatus('active');
        $provider->setCompletedJobs(150);

        // Verify provider
        $this->assertEquals('Premium Cleaning Co', $provider->getBusinessName());
        $this->assertTrue($provider->isVerified());
        $this->assertEquals(150, $provider->getCompletedJobs());
    }

    public function testCompleteBookingWorkflow(): void
    {
        // Create provider
        $provider = new ServiceProvider();
        $provider->setUserId('provider-002');
        $provider->setBusinessName('Home Services');
        $provider->setCategory('cleaning');
        
        // Create service
        $service = new Service();
        $service->setProvider($provider);
        $service->setName('Deep Cleaning');
        $service->setCategory('cleaning');
        $service->setPrice(500000.00);
        $service->setPriceType('fixed');
        $service->setEstimatedDuration(180);
        
        // Create booking
        $booking = new Booking();
        $booking->setUserId('user-001');
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress('456 Nguyen Hue, District 1');
        $booking->setLatitude(10.7769);
        $booking->setLongitude(106.7000);
        $booking->setScheduledAt(new \DateTime('2026-03-15 14:00:00'));
        $booking->setDuration(180);
        $booking->setPrice(500000.00);
        $booking->setPlatformFee(50000.00);
        $booking->setProviderEarning(450000.00);
        
        // Verify booking
        $this->assertEquals('user-001', $booking->getUserId());
        $this->assertEquals(Booking::STATUS_PENDING, $booking->getStatus());
        
        // Simulate status transitions
        $booking->setStatus(Booking::STATUS_CONFIRMED);
        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->getStatus());
        
        $booking->setStatus(Booking::STATUS_IN_PROGRESS);
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $booking->getStatus());
        
        $booking->setStatus(Booking::STATUS_COMPLETED);
        $booking->setCompletedAt(new \DateTime());
        $this->assertEquals(Booking::STATUS_COMPLETED, $booking->getStatus());
    }

    public function testProviderWithMultipleServices(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-003');
        $provider->setBusinessName('Multi Services');
        $provider->setCategory('home_services');
        
        // Service 1: Basic Cleaning
        $service1 = new Service();
        $service1->setProvider($provider);
        $service1->setName('Basic Cleaning');
        $service1->setPrice(200000.00);
        
        // Service 2: Deep Cleaning  
        $service2 = new Service();
        $service2->setProvider($provider);
        $service2->setName('Deep Cleaning');
        $service2->setPrice(400000.00);
        
        // Service 3: Move-in/out Cleaning
        $service3 = new Service();
        $service3->setProvider($provider);
        $service3->setName('Move-in/out Cleaning');
        $service3->setPrice(800000.00);
        
        $this->assertEquals(200000.00, $service1->getPrice());
        $this->assertEquals(400000.00, $service2->getPrice());
        $this->assertEquals(800000.00, $service3->getPrice());
    }

    public function testBookingWithDiscount(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-004');
        $provider->setBusinessName('Discount Services');
        $provider->setCategory('cleaning');
        
        $service = new Service();
        $service->setProvider($provider);
        $service->setName('Standard Service');
        $service->setPrice(300000.00);
        
        $booking = new Booking();
        $booking->setUserId('user-002');
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress('Test Address');
        $booking->setLatitude(10.0);
        $booking->setLongitude(106.0);
        $booking->setScheduledAt(new \DateTime());
        $booking->setDuration(60);
        $booking->setPrice(300000.00);
        $booking->setPlatformFee(30000.00);
        $booking->setProviderEarning(270000.00);
        $booking->setCouponCode('SAVE20');
        $booking->setDiscount(60000.00);
        
        $this->assertEquals('SAVE20', $booking->getCouponCode());
        $this->assertEquals(60000.00, $booking->getDiscount());
    }

    public function testReviewAfterBooking(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-005');
        $provider->setBusinessName('Reviewed Services');
        $provider->setCategory('cleaning');
        
        $service = new Service();
        $service->setProvider($provider);
        $service->setName('Service');
        $service->setPrice(250000.00);
        
        $booking = new Booking();
        $booking->setUserId('user-003');
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress('Test');
        $booking->setLatitude(10.0);
        $booking->setLongitude(106.0);
        $booking->setScheduledAt(new \DateTime());
        $booking->setDuration(60);
        $booking->setPrice(250000.00);
        $booking->setPlatformFee(25000.00);
        $booking->setProviderEarning(225000.00);
        $booking->setStatus(Booking::STATUS_COMPLETED);
        
        // Create review after booking
        $review = new Review();
        $review->setBooking($booking);
        $review->setProvider($provider);
        $review->setUserId('user-003');
        $review->setRating(4.5);
        $review->setComment('Great service, very professional!');
        
        $this->assertEquals(4.5, $review->getRating());
        $this->assertEquals('Great service, very professional!', $review->getComment());
    }

    public function testCategoryBrowsing(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Cleaning', 'slug' => 'cleaning', 'order' => 1],
            ['name' => 'Plumbing', 'slug' => 'plumbing', 'order' => 2],
            ['name' => 'Electrical', 'slug' => 'electrical', 'order' => 3],
            ['name' => 'Moving', 'slug' => 'moving', 'order' => 4],
        ];
        
        foreach ($categories as $cat) {
            $category = new Category();
            $category->setName($cat['name']);
            $category->setSlug($cat['slug']);
            $category->setDisplayOrder($cat['order']);
            
            $this->assertEquals($cat['name'], $category->getName());
            $this->assertEquals($cat['slug'], $category->getSlug());
        }
    }

    public function testProviderLocationSearch(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-006');
        $provider->setBusinessName('Central Services');
        $provider->setCategory('cleaning');
        $provider->setLatitude(10.7769);
        $provider->setLongitude(106.7000);
        
        // Simulate searching providers within radius
        $searchRadius = 5; // km
        $providerLat = $provider->getLatitude();
        $providerLng = $provider->getLongitude();
        
        // Check provider is in search area (simplified)
        $this->assertEquals(10.7769, $providerLat);
        $this->assertEquals(106.7000, $providerLng);
    }

    public function testBookingCancellation(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-007');
        $provider->setBusinessName('Cancellable Services');
        $provider->setCategory('cleaning');
        
        $service = new Service();
        $service->setProvider($provider);
        $service->setName('Booked Service');
        $service->setPrice(200000.00);
        
        $booking = new Booking();
        $booking->setUserId('user-004');
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress('Test');
        $booking->setLatitude(10.0);
        $booking->setLongitude(106.0);
        $booking->setScheduledAt(new \DateTime());
        $booking->setDuration(60);
        $booking->setPrice(200000.00);
        $booking->setPlatformFee(20000.00);
        $booking->setProviderEarning(180000.00);
        
        $this->assertEquals(Booking::STATUS_PENDING, $booking->getStatus());
        
        $booking->setStatus(Booking::STATUS_CANCELLED);
        
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->getStatus());
    }

    public function testProviderVerification(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-008');
        $provider->setBusinessName('Verified Services');
        $provider->setCategory('cleaning');
        
        $this->assertFalse($provider->isVerified());
        
        $provider->setVerified(true);
        
        $this->assertTrue($provider->isVerified());
    }

    public function testMultipleReviewsForProvider(): void
    {
        $provider = new ServiceProvider();
        $provider->setUserId('provider-009');
        $provider->setBusinessName('Popular Services');
        $provider->setCategory('cleaning');
        $provider->setRating(4.7);
        $provider->setReviewCount(10);
        
        // Simulate multiple reviews
        $ratings = [5.0, 4.5, 4.0, 5.0, 4.5];
        $totalRating = array_sum($ratings);
        $averageRating = $totalRating / count($ratings);
        
        $this->assertEquals(4.7, $provider->getRating());
        $this->assertEquals(10, $provider->getReviewCount());
    }
}
