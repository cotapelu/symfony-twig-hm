<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Vehicle;
use App\RideHailing\Entity\Payment;
use PHPUnit\Framework\TestCase;

/**
 * RideHailing Module Integration Test
 * 
 * Tests the RideHailing module as a real system.
 */
#[AllowMockObjectsWithoutExpectations]
class RideHailingIntegrationTest extends TestCase
{
    public function testCompleteRideWorkflow(): void
    {
        // Create driver
        $driver = new Driver();
        $driver->setUserId('driver-123');
        $driver->setLicensePlate('51A-99999');
        $driver->setVehicleType('car');
        $driver->setVehicleBrand('Toyota');
        $driver->setVehicleModel('Vios');
        $driver->setVehicleColor('white');
        $driver->setStatus(Driver::STATUS_ONLINE);
        $driver->setCurrentLatitude(10.8231);
        $driver->setCurrentLongitude(106.6297);

        // Create vehicle
        $vehicle = new Vehicle();
        $vehicle->setLicensePlate('51A-99999');
        $vehicle->setType('car');
        $vehicle->setBrand('Toyota');
        $vehicle->setModel('Vios');
        $vehicle->setColor('white');
        $vehicle->setYear(2023);

        // Create ride
        $ride = new Ride();
        $ride->setRiderId('rider-456');
        $ride->setPickupLatitude(10.8231);
        $ride->setPickupLongitude(106.6297);
        $ride->setPickupAddress('123 Nguyen Hue, District 1, HCMC');
        $ride->setDropoffLatitude(10.7769);
        $ride->setDropoffLongitude(106.7000);
        $ride->setDropoffAddress('456 Le Loi, District 1, HCMC');
        $ride->setEstimatedPrice(55000.00);
        $ride->setEstimatedDuration(1200);
        $ride->setRideType(Ride::TYPE_STANDARD);
        $ride->setDriver($driver);
        $ride->setVehicle($vehicle);

        // Verify initial state
        $this->assertEquals(Ride::STATUS_REQUESTED, $ride->getStatus());
        $this->assertTrue($ride->isActive());

        // Simulate driver accepting
        $ride->setStatus(Ride::STATUS_ACCEPTED);
        $ride->setAcceptedAt(new \DateTime());
        $this->assertEquals(Ride::STATUS_ACCEPTED, $ride->getStatus());

        // Simulate driver arriving
        $ride->setStatus(Ride::STATUS_ARRIVED);
        $ride->setArrivedAt(new \DateTime());
        $this->assertEquals(Ride::STATUS_ARRIVED, $ride->getStatus());

        // Simulate ride starting
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $ride->setStartedAt(new \DateTime());
        $this->assertEquals(Ride::STATUS_IN_PROGRESS, $ride->getStatus());

        // Simulate ride completing
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $ride->setCompletedAt(new \DateTime());
        $ride->setActualPrice(52000.00);
        $ride->setActualDuration(1100);
        $ride->setDistance(4500);

        // Verify completed state
        $this->assertTrue($ride->isCompleted());
        $this->assertFalse($ride->isActive());
        $this->assertEquals(52000.00, $ride->getActualPrice());
    }

    public function testRideWithPayment(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-1');
        $ride->setPickupLatitude(10.0);
        $ride->setPickupLongitude(106.0);
        $ride->setPickupAddress('Pickup');
        $ride->setEstimatedPrice(50000);
        $ride->setEstimatedDuration(600);

        // Create payment
        $payment = new Payment();
        $payment->setAmount(50000.00);
        $payment->setPaymentMethod(Payment::METHOD_CASH);
        $payment->setStatus(Payment::STATUS_PENDING);

        // Link payment to ride
        $ride->setPayment($payment);
        $payment->setRide($ride);

        $this->assertSame($payment, $ride->getPayment());
        $this->assertEquals(50000.00, $payment->getAmount());
    }

    public function testDriverLocationTracking(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-loc');
        $driver->setLicensePlate('51A-00001');
        $driver->setVehicleType('bike');
        
        // Simulate location updates
        $driver->setCurrentLatitude(10.8231);
        $driver->setCurrentLongitude(106.6297);
        
        $this->assertEquals(10.8231, $driver->getCurrentLatitude());
        $this->assertEquals(106.6297, $driver->getCurrentLongitude());
        
        // Update location
        $driver->setCurrentLatitude(10.8500);
        $driver->setCurrentLongitude(106.6500);
        
        $this->assertEquals(10.8500, $driver->getCurrentLatitude());
        $this->assertEquals(106.6500, $driver->getCurrentLongitude());
    }

    public function testMultipleRideTypes(): void
    {
        // Standard ride
        $standardRide = new Ride();
        $standardRide->setRiderId('rider-1');
        $standardRide->setPickupLatitude(10.0);
        $standardRide->setPickupLongitude(106.0);
        $standardRide->setPickupAddress('A');
        $standardRide->setEstimatedPrice(50000);
        $standardRide->setEstimatedDuration(600);
        $standardRide->setRideType(Ride::TYPE_STANDARD);

        // Premium ride
        $premiumRide = new Ride();
        $premiumRide->setRiderId('rider-2');
        $premiumRide->setPickupLatitude(10.0);
        $premiumRide->setPickupLongitude(106.0);
        $premiumRide->setPickupAddress('A');
        $premiumRide->setEstimatedPrice(100000);
        $premiumRide->setEstimatedDuration(600);
        $premiumRide->setRideType(Ride::TYPE_PREMIUM);

        // Bike ride
        $bikeRide = new Ride();
        $bikeRide->setRiderId('rider-3');
        $bikeRide->setPickupLatitude(10.0);
        $bikeRide->setPickupLongitude(106.0);
        $bikeRide->setPickupAddress('A');
        $bikeRide->setEstimatedPrice(20000);
        $bikeRide->setEstimatedDuration(900);
        $bikeRide->setRideType(Ride::TYPE_BIKE);

        $this->assertEquals(Ride::TYPE_STANDARD, $standardRide->getRideType());
        $this->assertEquals(Ride::TYPE_PREMIUM, $premiumRide->getRideType());
        $this->assertEquals(Ride::TYPE_BIKE, $bikeRide->getRideType());
    }

    public function testRideCancellation(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-cancel');
        $ride->setPickupLatitude(10.0);
        $ride->setPickupLongitude(106.0);
        $ride->setPickupAddress('Pickup');
        $ride->setEstimatedPrice(50000);
        $ride->setEstimatedDuration(600);

        $this->assertTrue($ride->isActive());
        
        $ride->setStatus(Ride::STATUS_CANCELLED);
        
        $this->assertTrue($ride->isCancelled());
        $this->assertFalse($ride->isActive());
    }

    public function testRideRatings(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-1');
        $ride->setPickupLatitude(10.0);
        $ride->setPickupLongitude(106.0);
        $ride->setPickupAddress('A');
        $ride->setEstimatedPrice(50000);
        $ride->setEstimatedDuration(600);
        $ride->setStatus(Ride::STATUS_COMPLETED);

        // Rider rates driver
        $ride->setDriverRating(5.0);
        $ride->setDriverReview('Excellent service!');
        
        // Driver rates rider
        $ride->setRiderRating(4.5);
        $ride->setRiderReview('Great passenger');

        $this->assertEquals(5.0, $ride->getDriverRating());
        $this->assertEquals('Excellent service!', $ride->getDriverReview());
        $this->assertEquals(4.5, $ride->getRiderRating());
        $this->assertEquals('Great passenger', $ride->getRiderReview());
    }

    public function testDriverAvailability(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-avail');
        $driver->setLicensePlate('51A-00002');
        $driver->setVehicleType('car');
        
        // Initially offline
        $this->assertFalse($driver->isOnline());
        $this->assertFalse($driver->isAvailable());
        
        // Go online
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertTrue($driver->isOnline());
        $this->assertTrue($driver->isAvailable());
        
        // Become busy
        $driver->setStatus(Driver::STATUS_BUSY);
        $this->assertTrue($driver->isOnline());
        $this->assertFalse($driver->isAvailable());
        
        // Go offline
        $driver->setStatus(Driver::STATUS_OFFLINE);
        $this->assertFalse($driver->isOnline());
        $this->assertFalse($driver->isAvailable());
    }

    public function testPaymentStatusTransitions(): void
    {
        $payment = new Payment();
        $payment->setAmount(50000.00);
        
        // Initially pending
        $this->assertEquals(Payment::STATUS_PENDING, $payment->getStatus());
        $this->assertFalse($payment->isCompleted());
        
        // Mark as completed
        $payment->setStatus(Payment::STATUS_COMPLETED);
        $payment->setPaidAt(new \DateTime());
        
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->getStatus());
        $this->assertTrue($payment->isCompleted());
    }

    public function testRideNotes(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-1');
        $ride->setPickupLatitude(10.0);
        $ride->setPickupLongitude(106.0);
        $ride->setPickupAddress('A');
        $ride->setEstimatedPrice(50000);
        $ride->setEstimatedDuration(600);
        
        $ride->setRiderNotes('Please arrive at the main entrance');
        
        $this->assertEquals('Please arrive at the main entrance', $ride->getRiderNotes());
    }

    public function testRideWithRiderInfo(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-info');
        $ride->setPickupLatitude(10.0);
        $ride->setPickupLongitude(106.0);
        $ride->setPickupAddress('A');
        $ride->setEstimatedPrice(50000);
        $ride->setEstimatedDuration(600);
        
        // Set rider rating after ride
        $ride->setRiderRating(4.8);
        $ride->setRiderReview('Polite and friendly');
        
        $this->assertEquals(4.8, $ride->getRiderRating());
        $this->assertEquals('Polite and friendly', $ride->getRiderReview());
    }

    public function testDriverRideStatistics(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-stats');
        $driver->setLicensePlate('51A-00003');
        $driver->setVehicleType('car');
        
        $driver->setTotalRides(150);
        $driver->setCompletedRides(145);
        $driver->setCancelledRides(5);
        
        $this->assertEquals(150, $driver->getTotalRides());
        $this->assertEquals(145, $driver->getCompletedRides());
        $this->assertEquals(5, $driver->getCancelledRides());
    }
}
