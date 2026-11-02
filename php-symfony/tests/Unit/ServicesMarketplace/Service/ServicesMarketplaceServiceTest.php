<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ServicesMarketplace\Service\ServicesMarketplaceService;
use App\ServicesMarketplace\Entity\ServiceProvider;
use App\ServicesMarketplace\Entity\Category;
use App\ServicesMarketplace\Repository\ServiceProviderRepository;
use App\ServicesMarketplace\Repository\ServiceRepository;
use App\ServicesMarketplace\Repository\BookingRepository;
use App\ServicesMarketplace\Repository\ReviewRepository;
use App\ServicesMarketplace\Repository\CategoryRepository;
use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Review;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServicesMarketplaceServiceTest extends TestCase
{
    private ServiceProviderRepository&MockObject $providerRepo;
    private ServiceRepository&MockObject $serviceRepo;
    private BookingRepository&MockObject $bookingRepo;
    private ReviewRepository&MockObject $reviewRepo;
    private CategoryRepository&MockObject $categoryRepo;
    private ServicesMarketplaceService $service;

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

    private function createProvider(int $id = 1, string $status = 'approved'): ServiceProvider
    {
        $provider = new ServiceProvider();
        $ref = new \ReflectionProperty(ServiceProvider::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($provider, $id);
        $provider->setStatus($status);
        $provider->setUserId('user-'.$id);
        $provider->setBusinessName('Provider '.$id);
        $provider->setCategory('cat');
        $provider->setAddress('addr');
        $provider->setLatitude(10.0);
        $provider->setLongitude(106.0);
        return $provider;
    }

    private function createService(int $id = 1, float $price = 100.0, ?int $duration = 60): Service
    {
        $service = new Service();
        $ref = new \ReflectionProperty(Service::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($service, $id);
        $service->setPrice($price);
        $service->setEstimatedDuration($duration);
        // setProvider required
        $provider = $this->createProvider($id);
        $service->setProvider($provider);
        $service->setName('Service '.$id);
        $service->setCategory('cat');
        return $service;
    }

    private function createBooking(int $id = 1, string $status = Booking::STATUS_PENDING): Booking
    {
        $booking = new Booking();
        $ref = new \ReflectionProperty(Booking::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($booking, $id);
        $booking->setStatus($status);
        $booking->setUserId('user-'.$id);
        $provider = $this->createProvider($id);
        $service = $this->createService($id);
        $booking->setProvider($provider);
        $booking->setService($service);
        $booking->setAddress('addr');
        $booking->setLatitude(10.0);
        $booking->setLongitude(106.0);
        $booking->setScheduledAt(new \DateTime('+1 day'));
        $booking->setDuration(60);
        $booking->setPrice(100.0);
        $booking->setPlatformFee(20.0);
        $booking->setProviderEarning(80.0);
        return $booking;
    }

    public function testRegisterProvider(): void
    {
        $data = [
            'userId' => 'user-123',
            'businessName' => 'Test Business',
            'category' => 'cleaning',
            'address' => '123 Main St',
            'latitude' => 10.0,
            'longitude' => 106.0,
            'phone' => '0123456789',
        ];

        $this->providerRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ServiceProvider $provider) use ($data) {
                return $provider->getUserId() === $data['userId']
                    && $provider->getBusinessName() === $data['businessName']
                    && $provider->getCategory() === $data['category']
                    && $provider->getAddress() === $data['address']
                    && $provider->getLatitude() === $data['latitude']
                    && $provider->getLongitude() === $data['longitude']
                    && $provider->getPhone() === $data['phone']
                    && $provider->getStatus() === 'pending';
            }))
            ->willReturnCallback(function (ServiceProvider $provider) {
                // Simulate setting an ID
                $ref = new \ReflectionProperty(ServiceProvider::class, 'id');
                $ref->setAccessible(true);
                $ref->setValue($provider, 1);
                return $provider;
            });

        $result = $this->service->registerProvider($data);

        $this->assertInstanceOf(ServiceProvider::class, $result);
        $this->assertSame(1, $result->getId());
    }

    public function testGetProviderByUserId(): void
    {
        $provider = new ServiceProvider();
        $this->providerRepo->expects($this->once())
            ->method('findByUserId')
            ->with('user-123')
            ->willReturn($provider);

        $result = $this->service->getProviderByUserId('user-123');

        $this->assertSame($provider, $result);
    }

    public function testGetCategories(): void
    {
        $categories = [new Category(), new Category()];
        $this->categoryRepo->expects($this->once())
            ->method('findActiveCategories')
            ->willReturn($categories);

        $result = $this->service->getCategories();

        $this->assertSame($categories, $result);
    }

    public function testCreateBookingSuccess(): void
    {
        $provider = $this->createProvider(1, 'approved');
        $service = $this->createService(1);
        $booking = $this->createBooking(1);

        $this->providerRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($provider);
        $this->serviceRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($service);
        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Booking $b) use ($provider, $service) {
                return $b->getProvider() === $provider
                    && $b->getService() === $service
                    && $b->getStatus() === Booking::STATUS_PENDING;
            }))
            ->willReturnCallback(function (Booking $b) {
                $ref = new \ReflectionProperty(Booking::class, 'id');
                $ref->setAccessible(true);
                $ref->setValue($b, 1);
                return $b;
            });
        $this->serviceRepo->expects($this->once())
            ->method('incrementBookedCount')
            ->with(1);

        $data = [
            'providerId' => 1,
            'serviceId' => 1,
            'userId' => 'user-1',
            'address' => '123 Main',
            'latitude' => 10.0,
            'longitude' => 106.0,
            'scheduledAt' => (new \DateTime('+1 day'))->format('Y-m-d H:i:s'),
        ];

        $result = $this->service->createBooking($data);
        $this->assertInstanceOf(Booking::class, $result);
        $this->assertSame(1, $result->getId());
    }

    public function testCreateBookingProviderNotApprovedThrows(): void
    {
        $provider = $this->createProvider(1, 'pending');
        $this->providerRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($provider);
        $this->serviceRepo->expects($this->never())
            ->method('find');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Provider not available');

        $this->service->createBooking(['providerId'=>1, 'serviceId'=>1, 'userId'=>'u', 'address'=>'a', 'latitude'=>10, 'longitude'=>106, 'scheduledAt'=>'2025-01-01 00:00:00']);
    }

    public function testCreateBookingServiceNotFoundThrows(): void
    {
        $provider = $this->createProvider(1, 'approved');
        $this->providerRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($provider);
        $this->serviceRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found');

        $this->service->createBooking(['providerId'=>1, 'serviceId'=>1, 'userId'=>'u', 'address'=>'a', 'latitude'=>10, 'longitude'=>106, 'scheduledAt'=>'2025-01-01 00:00:00']);
    }

    public function testConfirmBookingSuccess(): void
    {
        $booking = $this->createBooking(1, Booking::STATUS_PENDING);
        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);
        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($booking);

        $result = $this->service->confirmBooking(1);
        $this->assertSame($booking, $result);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->getStatus());
    }

    public function testConfirmBookingWhenNotFoundOrWrongStatusReturnsNull(): void
    {
        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $result = $this->service->confirmBooking(1);
        $this->assertNull($result);
    }

    public function testStartBookingSuccess(): void
    {
        $booking = $this->createBooking(1, Booking::STATUS_CONFIRMED);
        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);
        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($booking);

        $result = $this->service->startBooking(1);
        $this->assertSame($booking, $result);
        $this->assertSame(Booking::STATUS_IN_PROGRESS, $booking->getStatus());
    }

    public function testCompleteBookingSuccess(): void
    {
        $booking = $this->createBooking(1, Booking::STATUS_IN_PROGRESS);
        $provider = $booking->getProvider();

        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);
        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($booking);
        $this->providerRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ServiceProvider $p) use ($provider) {
                return $p->getId() === $provider->getId();
            }));

        $result = $this->service->completeBooking(1);
        $this->assertSame($booking, $result);
        $this->assertSame(Booking::STATUS_COMPLETED, $booking->getStatus());
        $this->assertNotNull($booking->getCompletedAt());
        $this->assertSame(1, $provider->getCompletedJobs());
    }

    public function testCancelBookingSuccess(): void
    {
        $booking = $this->createBooking(1, Booking::STATUS_PENDING);
        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);
        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($booking);

        $result = $this->service->cancelBooking(1, 'changed mind');
        $this->assertSame($booking, $result);
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->getStatus());
    }

    public function testAddReviewSuccess(): void
    {
        $booking = $this->createBooking(1, Booking::STATUS_COMPLETED);
        $provider = $booking->getProvider();
        $review = new Review();
        $review->setRating(4.5);

        $this->bookingRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);
        $this->reviewRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Review $r) use ($booking, $provider) {
                return $r->getBooking() === $booking
                    && $r->getProvider() === $provider
                    && $r->getUserId() === 'user-1'
                    && $r->getRating() === 4.5;
            }));
        $this->reviewRepo->expects($this->once())
            ->method('findByProvider')
            ->with($provider->getId())
            ->willReturn([$review]);
        $this->providerRepo->expects($this->once())
            ->method('updateRating')
            ->with($provider->getId(), $this->callback(fn($avg) => abs($avg - 4.5) < 0.001), $this->equalTo(1));

        $data = [
            'bookingId' => 1,
            'userId' => 'user-1',
            'rating' => 4.5,
            'comment' => 'Great',
            'serviceType' => 'cleaning',
        ];

        $result = $this->service->addReview($data);
        $this->assertInstanceOf(Review::class, $result);
    }

    public function testGetProviderStats(): void
    {
        $provider = $this->createProvider(1);
        $booking1 = $this->createBooking(1, Booking::STATUS_COMPLETED);
        $booking1->setProvider($provider);
        $booking2 = $this->createBooking(2, Booking::STATUS_PENDING);
        $booking2->setProvider($provider);
        $bookings = [$booking1, $booking2];

        $this->bookingRepo->expects($this->once())
            ->method('findByProvider')
            ->with(1)
            ->willReturn($bookings);

        $result = $this->service->getProviderStats(1);
        $this->assertSame(2, $result['total_bookings']);
        $this->assertSame(1, $result['completed_bookings']);
        $this->assertSame(80.0, $result['total_earnings']);
        $this->assertSame(1, $result['pending_bookings']);
    }
}
