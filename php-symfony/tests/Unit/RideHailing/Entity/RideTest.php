<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Vehicle;
use App\RideHailing\Entity\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RideTest extends TestCase
{
    private function createMockDriver(): MockObject&Driver
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('getId')->willReturn(1);
        return $driver;
    }

    private function createMockVehicle(): MockObject&Vehicle
    {
        $vehicle = $this->createMock(Vehicle::class);
        $vehicle->method('getId')->willReturn(2);
        return $vehicle;
    }

    private function createMockPayment(): MockObject&Payment
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getId')->willReturn(3);
        return $payment;
    }

    public function testConstructorInitializesFields(): void
    {
        $ride = new Ride();
        $this->assertIsString($ride->getRideNumber());
        $this->assertStringStartsWith('RIDE-', $ride->getRideNumber());
        $this->assertInstanceOf(\DateTime::class, $ride->getRequestedAt());
        $this->assertSame(Ride::STATUS_REQUESTED, $ride->getStatus());
        $this->assertSame(Ride::TYPE_STANDARD, $ride->getRideType());
    }

    public function testGettersSetters(): void
    {
        $driver = $this->createMockDriver();
        $vehicle = $this->createMockVehicle();
        $payment = $this->createMockPayment();

        $ride = new Ride();

        $ride->setDriver($driver);
        $this->assertSame($driver, $ride->getDriver());

        $ride->setRiderId('rider-123');
        $this->assertSame('rider-123', $ride->getRiderId());

        $ride->setStatus(Ride::STATUS_ACCEPTED);
        $this->assertSame(Ride::STATUS_ACCEPTED, $ride->getStatus());

        $ride->setRideType(Ride::TYPE_PREMIUM);
        $this->assertSame(Ride::TYPE_PREMIUM, $ride->getRideType());

        $ride->setPickupLatitude(10.123456);
        $this->assertSame(10.123456, $ride->getPickupLatitude());

        $ride->setPickupLongitude(20.654321);
        $this->assertSame(20.654321, $ride->getPickupLongitude());

        $ride->setPickupAddress('123 Main St');
        $this->assertSame('123 Main St', $ride->getPickupAddress());

        $ride->setDropoffLatitude(15.111111);
        $this->assertSame(15.111111, $ride->getDropoffLatitude());

        $ride->setDropoffLongitude(30.222222);
        $this->assertSame(30.222222, $ride->getDropoffLongitude());

        $ride->setDropoffAddress('456 Elm St');
        $this->assertSame('456 Elm St', $ride->getDropoffAddress());

        $ride->setEstimatedPrice(25.00);
        $this->assertSame(25.00, $ride->getEstimatedPrice());

        $ride->setActualPrice(23.50);
        $this->assertSame(23.50, $ride->getActualPrice());

        $ride->setEstimatedDuration(15);
        $this->assertSame(15, $ride->getEstimatedDuration());

        $ride->setActualDuration(14);
        $this->assertSame(14, $ride->getActualDuration());

        $ride->setDistance(5);
        $this->assertSame(5, $ride->getDistance());

        $ride->setRiderNotes('Call on arrival');
        $this->assertSame('Call on arrival', $ride->getRiderNotes());

        $ride->setVehicle($vehicle);
        $this->assertSame($vehicle, $ride->getVehicle());

        $accepted = new \DateTime('2025-01-01 10:00:00');
        $ride->setAcceptedAt($accepted);
        $this->assertSame($accepted, $ride->getAcceptedAt());

        $arrived = new \DateTime('2025-01-01 10:10:00');
        $ride->setArrivedAt($arrived);
        $this->assertSame($arrived, $ride->getArrivedAt());

        $started = new \DateTime('2025-01-01 10:15:00');
        $ride->setStartedAt($started);
        $this->assertSame($started, $ride->getStartedAt());

        $completed = new \DateTime('2025-01-01 10:30:00');
        $ride->setCompletedAt($completed);
        $this->assertSame($completed, $ride->getCompletedAt());

        $ride->setPayment($payment);
        $this->assertSame($payment, $ride->getPayment());
    }

    public function testStateMethods(): void
    {
        $ride = new Ride();

        $this->assertFalse($ride->isCompleted());
        $this->assertFalse($ride->isCancelled());
        $this->assertTrue($ride->isActive()); // requested is active

        $ride->setStatus(Ride::STATUS_COMPLETED);
        $this->assertTrue($ride->isCompleted());
        $this->assertFalse($ride->isActive());

        $ride->setStatus(Ride::STATUS_CANCELLED);
        $this->assertTrue($ride->isCancelled());
        $this->assertFalse($ride->isActive());

        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $this->assertTrue($ride->isActive());
    }

    public function testIdSetAndGet(): void
    {
        $ride = new Ride();
        $ref = new \ReflectionProperty($ride, 'id');
        $ref->setAccessible(true);
        $ref->setValue($ride, 999);
        $this->assertSame(999, $ride->getId());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('requested', Ride::STATUS_REQUESTED);
        $this->assertSame('accepted', Ride::STATUS_ACCEPTED);
        $this->assertSame('arrived', Ride::STATUS_ARRIVED);
        $this->assertSame('in_progress', Ride::STATUS_IN_PROGRESS);
        $this->assertSame('completed', Ride::STATUS_COMPLETED);
        $this->assertSame('cancelled', Ride::STATUS_CANCELLED);
    }

    public function testTypeConstants(): void
    {
        $this->assertSame('standard', Ride::TYPE_STANDARD);
        $this->assertSame('premium', Ride::TYPE_PREMIUM);
        $this->assertSame('bike', Ride::TYPE_BIKE);
        $this->assertSame('delivery', Ride::TYPE_DELIVERY);
    }
}
