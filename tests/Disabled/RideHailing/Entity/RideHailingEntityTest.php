<?php declare(strict_types=1);

namespace App\Tests\Unit\RideHailing\Entity;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Vehicle;
use App\RideHailing\Entity\Payment;
use PHPUnit\Framework\TestCase;

class RideHailingEntityTest extends TestCase
{
    // ==================== RIDE TESTS ====================
    
    public function testRideCreation(): void
    {
        $ride = new Ride();
        $ride->setRiderId('rider-123');
        $ride->setPickupLatitude(10.8231);
        $ride->setPickupLongitude(106.6297);
        $ride->setPickupAddress('123 Nguyen Hue, District 1, HCMC');
        $ride->setDropoffLatitude(10.7769);
        $ride->setDropoffLongitude(106.7000);
        $ride->setDropoffAddress('456 Le Loi, District 1, HCMC');
        $ride->setEstimatedPrice(50000.00);
        $ride->setEstimatedDuration(1800);
        $ride->setRideType(Ride::TYPE_STANDARD);

        $this->assertEquals('rider-123', $ride->getRiderId());
        $this->assertEquals(10.8231, $ride->getPickupLatitude());
        $this->assertEquals(106.6297, $ride->getPickupLongitude());
        $this->assertEquals('123 Nguyen Hue, District 1, HCMC', $ride->getPickupAddress());
        $this->assertEquals(50000.00, $ride->getEstimatedPrice());
        $this->assertEquals(1800, $ride->getEstimatedDuration());
        $this->assertEquals(Ride::TYPE_STANDARD, $ride->getRideType());
    }

    public function testRideStatusConstants(): void
    {
        $this->assertEquals('requested', Ride::STATUS_REQUESTED);
        $this->assertEquals('accepted', Ride::STATUS_ACCEPTED);
        $this->assertEquals('arrived', Ride::STATUS_ARRIVED);
        $this->assertEquals('in_progress', Ride::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', Ride::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Ride::STATUS_CANCELLED);
    }

    public function testRideTypeConstants(): void
    {
        $this->assertEquals('standard', Ride::TYPE_STANDARD);
        $this->assertEquals('premium', Ride::TYPE_PREMIUM);
        $this->assertEquals('bike', Ride::TYPE_BIKE);
        $this->assertEquals('delivery', Ride::TYPE_DELIVERY);
    }

    public function testRideDefaultStatus(): void
    {
        $ride = new Ride();
        $this->assertEquals(Ride::STATUS_REQUESTED, $ride->getStatus());
    }

    public function testRideDefaultRideType(): void
    {
        $ride = new Ride();
        $this->assertEquals(Ride::TYPE_STANDARD, $ride->getRideType());
    }

    public function testRideStatusTransitions(): void
    {
        $ride = new Ride();
        
        $ride->setStatus(Ride::STATUS_ACCEPTED);
        $this->assertEquals(Ride::STATUS_ACCEPTED, $ride->getStatus());
        
        $ride->setStatus(Ride::STATUS_ARRIVED);
        $this->assertEquals(Ride::STATUS_ARRIVED, $ride->getStatus());
        
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $this->assertEquals(Ride::STATUS_IN_PROGRESS, $ride->getStatus());
        
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $this->assertEquals(Ride::STATUS_COMPLETED, $ride->getStatus());
    }

    public function testRideIsCompleted(): void
    {
        $ride = new Ride();
        $this->assertFalse($ride->isCompleted());
        
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $this->assertTrue($ride->isCompleted());
    }

    public function testRideIsCancelled(): void
    {
        $ride = new Ride();
        $this->assertFalse($ride->isCancelled());
        
        $ride->setStatus(Ride::STATUS_CANCELLED);
        $this->assertTrue($ride->isCancelled());
    }

    public function testRideIsActive(): void
    {
        $ride = new Ride();
        
        // Requested is active
        $this->assertTrue($ride->isActive());
        
        // Accepted is active
        $ride->setStatus(Ride::STATUS_ACCEPTED);
        $this->assertTrue($ride->isActive());
        
        // In progress is active
        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $this->assertTrue($ride->isActive());
        
        // Completed is not active
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $this->assertFalse($ride->isActive());
        
        // Cancelled is not active
        $ride->setStatus(Ride::STATUS_CANCELLED);
        $this->assertFalse($ride->isActive());
    }

    public function testRideWithDriver(): void
    {
        $driver = new Driver();
        $driver->setUserId('driver-123');
        $driver->setLicensePlate('51A-12345');
        $driver->setVehicleType('car');
        
        $ride = new Ride();
        $ride->setDriver($driver);
        
        $this->assertSame($driver, $ride->getDriver());
    }

    public function testRideWithVehicle(): void
    {
        $vehicle = new Vehicle();
        $vehicle->setLicensePlate('51A-12345');
        $vehicle->setType('car');
        
        $ride = new Ride();
        $ride->setVehicle($vehicle);
        
        $this->assertSame($vehicle, $ride->getVehicle());
    }

    public function testRideWithPayment(): void
    {
        $payment = new Payment();
        $payment->setAmount(50000.00);
        
        $ride = new Ride();
        $ride->setPayment($payment);
        
        $this->assertSame($payment, $ride->getPayment());
    }

    public function testRideTimestamps(): void
    {
        $ride = new Ride();
        
        $this->assertInstanceOf(\DateTime::class, $ride->getRequestedAt());
        
        $ride->setAcceptedAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $ride->getAcceptedAt());
        
        $ride->setArrivedAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $ride->getArrivedAt());
        
        $ride->setStartedAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $ride->getStartedAt());
        
        $ride->setCompletedAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $ride->getCompletedAt());
    }

    public function testRideRatings(): void
    {
        $ride = new Ride();
        
        $ride->setRiderRating(4.5);
        $this->assertEquals(4.5, $ride->getRiderRating());
        
        $ride->setDriverRating(5.0);
        $this->assertEquals(5.0, $ride->getDriverRating());
    }

    public function testRideReviews(): void
    {
        $ride = new Ride();
        
        $ride->setRiderReview('Great driver!');
        $this->assertEquals('Great driver!', $ride->getRiderReview());
        
        $ride->setDriverReview('Nice passenger');
        $this->assertEquals('Nice passenger', $ride->getDriverReview());
    }

    public function testRideNumberGeneration(): void
    {
        $ride = new Ride();
        
        $rideNumber = $ride->getRideNumber();
        
        $this->assertStringStartsWith('RIDE-', $rideNumber);
        $this->assertGreaterThanOrEqual(14, strlen($rideNumber)); // RIDE- + at least 12 hex chars
    }

    public function testRidePricing(): void
    {
        $ride = new Ride();
        
        $ride->setEstimatedPrice(75000.00);
        $this->assertEquals(75000.00, $ride->getEstimatedPrice());
        
        $ride->setActualPrice(68000.00);
        $this->assertEquals(68000.00, $ride->getActualPrice());
    }

    public function testRideDistanceAndDuration(): void
    {
        $ride = new Ride();
        
        $ride->setDistance(5000); // 5km
        $this->assertEquals(5000, $ride->getDistance());
        
        $ride->setEstimatedDuration(1200); // 20 minutes
        $this->assertEquals(1200, $ride->getEstimatedDuration());
        
        $ride->setActualDuration(1100); // ~18 minutes
        $this->assertEquals(1100, $ride->getActualDuration());
    }

    public function testRideNotes(): void
    {
        $ride = new Ride();
        
        $ride->setRiderNotes('Please drive carefully');
        $this->assertEquals('Please drive carefully', $ride->getRiderNotes());
    }

    public function testFluentInterface(): void
    {
        $ride = new Ride();
        
        $result = $ride
            ->setRiderId('rider-1')
            ->setPickupLatitude(10.0)
            ->setPickupLongitude(106.0)
            ->setPickupAddress('Test Address')
            ->setEstimatedPrice(50000)
            ->setEstimatedDuration(1800)
            ->setStatus(Ride::STATUS_ACCEPTED);
        
        $this->assertInstanceOf(Ride::class, $result);
    }

    // ==================== DRIVER TESTS ====================
    
    public function testDriverCreation(): void
    {
        $driver = new Driver();
        $driver->setUserId('user-123');
        $driver->setLicensePlate('51A-12345');
        $driver->setVehicleType('car');
        $driver->setVehicleBrand('Toyota');
        $driver->setVehicleModel('Vios');
        $driver->setVehicleColor('white');
        $driver->setVehicleYear(2023);
        $driver->setRating(4.8);
        $driver->setStatus(Driver::STATUS_ONLINE);

        $this->assertEquals('user-123', $driver->getUserId());
        $this->assertEquals('51A-12345', $driver->getLicensePlate());
        $this->assertEquals('car', $driver->getVehicleType());
        $this->assertEquals('Toyota', $driver->getVehicleBrand());
        $this->assertEquals('Vios', $driver->getVehicleModel());
        $this->assertEquals('white', $driver->getVehicleColor());
        $this->assertEquals(2023, $driver->getVehicleYear());
        $this->assertEquals(4.8, $driver->getRating());
    }

    public function testDriverStatusConstants(): void
    {
        $this->assertEquals('offline', Driver::STATUS_OFFLINE);
        $this->assertEquals('online', Driver::STATUS_ONLINE);
        $this->assertEquals('busy', Driver::STATUS_BUSY);
        $this->assertEquals('ride_request', Driver::STATUS_RIDE_REQUEST);
    }

    public function testDriverDefaultStatus(): void
    {
        $driver = new Driver();
        $this->assertEquals(Driver::STATUS_OFFLINE, $driver->getStatus());
    }

    public function testDriverDefaultRating(): void
    {
        $driver = new Driver();
        $this->assertEquals(5.00, $driver->getRating());
    }

    public function testDriverLocation(): void
    {
        $driver = new Driver();
        
        $driver->setCurrentLatitude(10.8231);
        $driver->setCurrentLongitude(106.6297);
        
        $this->assertEquals(10.8231, $driver->getCurrentLatitude());
        $this->assertEquals(106.6297, $driver->getCurrentLongitude());
    }

    public function testDriverRideCounts(): void
    {
        $driver = new Driver();
        
        $driver->setTotalRides(100);
        $driver->setCompletedRides(95);
        $driver->setCancelledRides(5);
        
        $this->assertEquals(100, $driver->getTotalRides());
        $this->assertEquals(95, $driver->getCompletedRides());
        $this->assertEquals(5, $driver->getCancelledRides());
    }

    public function testDriverVerification(): void
    {
        $driver = new Driver();
        
        $this->assertFalse($driver->isVerified());
        $driver->setVerified(true);
        $this->assertTrue($driver->isVerified());
    }

    public function testDriverActiveStatus(): void
    {
        $driver = new Driver();
        
        $this->assertTrue($driver->isActive());
        $driver->setActive(false);
        $this->assertFalse($driver->isActive());
    }

    public function testDriverIsOnline(): void
    {
        $driver = new Driver();
        
        $this->assertFalse($driver->isOnline());
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertTrue($driver->isOnline());
    }

    public function testDriverIsAvailable(): void
    {
        $driver = new Driver();
        
        // Not available when offline
        $this->assertFalse($driver->isAvailable());
        
        // Available when online and active
        $driver->setStatus(Driver::STATUS_ONLINE);
        $this->assertTrue($driver->isAvailable());
        
        // Not available when inactive
        $driver->setActive(false);
        $this->assertFalse($driver->isAvailable());
    }

    public function testDriverBankDetails(): void
    {
        $driver = new Driver();
        
        $driver->setBankAccountNumber('1234567890');
        $driver->setBankName('Vietcombank');
        $driver->setBankAccountName('John Doe');
        
        $this->assertEquals('1234567890', $driver->getBankAccountNumber());
        $this->assertEquals('Vietcombank', $driver->getBankName());
        $this->assertEquals('John Doe', $driver->getBankAccountName());
    }

    public function testDriverLastActiveAt(): void
    {
        $driver = new Driver();
        
        $this->assertInstanceOf(\DateTime::class, $driver->getLastActiveAt());
        
        $newTime = new \DateTime();
        $driver->setLastActiveAt($newTime);
        $this->assertEquals($newTime, $driver->getLastActiveAt());
    }

    // ==================== VEHICLE TESTS ====================
    
    public function testVehicleCreation(): void
    {
        $vehicle = new Vehicle();
        $vehicle->setLicensePlate('51A-12345');
        $vehicle->setType('car');
        $vehicle->setBrand('Toyota');
        $vehicle->setModel('Vios');
        $vehicle->setYear(2023);
        $vehicle->setColor('white');

        $this->assertEquals('51A-12345', $vehicle->getLicensePlate());
        $this->assertEquals('car', $vehicle->getType());
        $this->assertEquals('Toyota', $vehicle->getBrand());
        $this->assertEquals('Vios', $vehicle->getModel());
        $this->assertEquals(2023, $vehicle->getYear());
        $this->assertEquals('white', $vehicle->getColor());
    }




    public function testVehicleDefaultType(): void
    {
        $vehicle = new Vehicle();
        $vehicle->setType('car');
        $this->assertEquals('car', $vehicle->getType());
    }

    // ==================== PAYMENT TESTS ====================
    
    public function testPaymentCreation(): void
    {
        $payment = new Payment();
        $payment->setAmount(50000.00);
        $payment->setPaymentMethod('cash');
        $payment->setStatus('pending');

        $this->assertEquals(50000.00, $payment->getAmount());
        $this->assertEquals('cash', $payment->getPaymentMethod());
        $this->assertEquals('pending', $payment->getStatus());
    }




    public function testPaymentDefaultStatus(): void
    {
        $payment = new Payment();
        $this->assertEquals('pending', $payment->getStatus());
    }

    public function testPaymentMethods(): void
    {
        $payment = new Payment();
        
        $payment->setPaymentMethod('cash');
        $this->assertEquals('cash', $payment->getPaymentMethod());
        
        $payment->setPaymentMethod('card');
        $this->assertEquals('card', $payment->getPaymentMethod());
        
        $payment->setPaymentMethod('wallet');
        $this->assertEquals('wallet', $payment->getPaymentMethod());
    }
}
