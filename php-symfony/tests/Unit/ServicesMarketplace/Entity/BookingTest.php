<?php declare(strict_types=1);

namespace App\Tests\Unit\ServicesMarketplace\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ServicesMarketplace\Entity\Booking;
use App\ServicesMarketplace\Entity\Service;
use App\ServicesMarketplace\Entity\ServiceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BookingTest extends TestCase
{
    private function createMockProvider(): MockObject&ServiceProvider
    {
        $provider = $this->createMock(ServiceProvider::class);
        $provider->method('getId')->willReturn(1);
        return $provider;
    }

    private function createMockService(): MockObject&Service
    {
        $service = $this->createMock(Service::class);
        $service->method('getId')->willReturn(2);
        return $service;
    }

    public function testConstructorInitializesCreatedAtAndDefaultStatus(): void
    {
        $booking = new Booking();
        $this->assertInstanceOf(\DateTime::class, $booking->getCreatedAt());
        $this->assertSame(Booking::STATUS_PENDING, $booking->getStatus());
    }

    public function testGettersSetters(): void
    {
        $provider = $this->createMockProvider();
        $service = $this->createMockService();

        $booking = new Booking();

        $booking->setUserId('user-123');
        $this->assertSame('user-123', $booking->getUserId());

        $booking->setProvider($provider);
        $this->assertSame($provider, $booking->getProvider());

        $booking->setService($service);
        $this->assertSame($service, $booking->getService());

        $booking->setAddress('123 Main St');
        $this->assertSame('123 Main St', $booking->getAddress());

        $booking->setLatitude(10.123456);
        $this->assertSame(10.123456, $booking->getLatitude());

        $booking->setLongitude(20.654321);
        $this->assertSame(20.654321, $booking->getLongitude());

        $scheduled = new \DateTime('2025-01-01 09:00:00');
        $booking->setScheduledAt($scheduled);
        $this->assertSame($scheduled, $booking->getScheduledAt());

        $booking->setDuration(60);
        $this->assertSame(60, $booking->getDuration());

        $booking->setStatus(Booking::STATUS_CONFIRMED);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->getStatus());

        $booking->setPrice(100.00);
        $this->assertSame(100.00, $booking->getPrice());

        $booking->setPlatformFee(10.00);
        $this->assertSame(10.00, $booking->getPlatformFee());

        $booking->setProviderEarning(90.00);
        $this->assertSame(90.00, $booking->getProviderEarning());

        $booking->setPaymentMethod('card');
        $this->assertSame('card', $booking->getPaymentMethod());

        $booking->setPaymentStatus('paid');
        $this->assertSame('paid', $booking->getPaymentStatus());

        $booking->setUserNote('Please call on arrival');
        $this->assertSame('Please call on arrival', $booking->getUserNote());

        $booking->setCouponCode('SAVE10');
        $this->assertSame('SAVE10', $booking->getCouponCode());

        $booking->setDiscount(5.00);
        $this->assertSame(5.00, $booking->getDiscount());

        $completed = new \DateTime('2025-01-01 10:00:00');
        $booking->setCompletedAt($completed);
        $this->assertSame($completed, $booking->getCompletedAt());

        // Test null assignments
        $booking->setPaymentMethod(null);
        $this->assertNull($booking->getPaymentMethod());

        $booking->setPaymentStatus(null);
        $this->assertNull($booking->getPaymentStatus());

        $booking->setUserNote(null);
        $this->assertNull($booking->getUserNote());

        $booking->setCouponCode(null);
        $this->assertNull($booking->getCouponCode());

        $booking->setDiscount(null);
        $this->assertNull($booking->getDiscount());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', Booking::STATUS_PENDING);
        $this->assertSame('confirmed', Booking::STATUS_CONFIRMED);
        $this->assertSame('in_progress', Booking::STATUS_IN_PROGRESS);
        $this->assertSame('completed', Booking::STATUS_COMPLETED);
        $this->assertSame('cancelled', Booking::STATUS_CANCELLED);
        $this->assertSame('disputed', Booking::STATUS_DISPUTED);
    }

    public function testIdSetAndGet(): void
    {
        $booking = new Booking();
        $ref = new \ReflectionProperty($booking, 'id');
        $ref->setAccessible(true);
        $ref->setValue($booking, 100);
        $this->assertSame(100, $booking->getId());
    }
}
