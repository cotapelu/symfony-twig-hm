<?php declare(strict_types=1);

namespace App\Tests\Unit\Business\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Business\Entity\ServiceBooking;
use App\Core\Entity\User;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceBookingTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPasswordHash('hashed');
        return $user;
    }

    private function createBooking(?User $provider = null, ?User $customer = null): ServiceBooking
    {
        return new ServiceBooking(
            provider: $provider ?? $this->createUser(),
            customer: $customer ?? $this->createUser()
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $booking = $this->createBooking();
        $this->assertIsString($booking->getId());
        $this->assertNotEmpty($booking->getId());
        $this->assertInstanceOf(User::class, $booking->getProvider());
        $this->assertInstanceOf(User::class, $booking->getCustomer());
        $this->assertNull($booking->getServiceType());
        $this->assertNull($booking->getScheduledAt());
        $this->assertSame(ServiceBooking::STATUS_PENDING, $booking->getStatus());
        $this->assertTrue($booking->isPending());
        $this->assertNull($booking->getData());
        $this->assertInstanceOf(\DateTime::class, $booking->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $booking->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $booking = $this->createBooking();

        $booking->setServiceType('cleaning');
        $this->assertSame('cleaning', $booking->getServiceType());

        $scheduled = new \DateTime('2025-01-01 10:00:00');
        $booking->setScheduledAt($scheduled);
        $this->assertSame($scheduled, $booking->getScheduledAt());

        $booking->setStatus(ServiceBooking::STATUS_CONFIRMED);
        $this->assertSame(ServiceBooking::STATUS_CONFIRMED, $booking->getStatus());
        $this->assertTrue($booking->isConfirmed());
        $this->assertFalse($booking->isPending());

        $booking->setStatus(ServiceBooking::STATUS_IN_PROGRESS); // no isInProgress method; only state check
        // Note: no isInProgress method, but we can assert status directly.
        $this->assertSame(ServiceBooking::STATUS_IN_PROGRESS, $booking->getStatus());

        $booking->setStatus(ServiceBooking::STATUS_COMPLETED);
        $this->assertTrue($booking->isCompleted());

        $booking->setStatus(ServiceBooking::STATUS_CANCELLED);
        $this->assertTrue($booking->isCancelled());

        $data = ['address' => '123 Main'];
        $booking->setData($data);
        $this->assertSame($data, $booking->getData());
    }

    public function testToArray(): void
    {
        $provider = $this->createUser();
        $customer = $this->createUser();
        $booking = new ServiceBooking($provider, $customer);
        $booking->setServiceType('plumbing');
        $scheduled = new \DateTime('2025-05-20 14:30:00');
        $booking->setScheduledAt($scheduled);
        $booking->setStatus(ServiceBooking::STATUS_CONFIRMED);
        $booking->setData(['notes' => 'Fix leak']);

        $arr = $booking->toArray();
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame($provider->getId(), $arr['providerId']);
        $this->assertSame($customer->getId(), $arr['customerId']);
        $this->assertSame('plumbing', $arr['serviceType']);
        $this->assertSame('2025-05-20 14:30:00', $arr['scheduledAt']);
        $this->assertSame(ServiceBooking::STATUS_CONFIRMED, $arr['status']);
        $this->assertSame(['notes' => 'Fix leak'], $arr['data']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['createdAt']);
    }
}
