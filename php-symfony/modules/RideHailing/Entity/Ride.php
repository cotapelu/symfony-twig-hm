<?php

declare(strict_types=1);

namespace App\RideHailing\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\RideHailing\Repository\RideRepository::class)
 * @ORM\Table(name="ride_hailing_rides")
 */
class Ride
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_STANDARD = 'standard';
    public const TYPE_PREMIUM = 'premium';
    public const TYPE_BIKE = 'bike';
    public const TYPE_DELIVERY = 'delivery';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private string $rideNumber;

    /**
     * @ORM\ManyToOne(targetEntity=Driver::class)
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id", nullable=true)
     */
    private ?Driver $driver;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private string $riderId;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $status = self::STATUS_REQUESTED;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $rideType = self::TYPE_STANDARD;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6)
     */
    private float $pickupLatitude;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6)
     */
    private float $pickupLongitude;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $pickupAddress;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6, nullable=true)
     */
    private ?float $dropoffLatitude;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6, nullable=true)
     */
    private ?float $dropoffLongitude;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dropoffAddress;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private float $estimatedPrice;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?float $actualPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private int $estimatedDuration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $actualDuration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $distance;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $riderNotes;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class)
     * @ORM\JoinColumn(name="vehicle_id", referencedColumnName="id", nullable=true)
     */
    private ?Vehicle $vehicle;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $acceptedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $arrivedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $completedAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $requestedAt;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private ?float $riderRating;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private ?float $driverRating;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $riderReview;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $driverReview;

    /**
     * @ORM\OneToOne(targetEntity=Payment::class, cascade={"persist", "remove"})
     */
    private ?Payment $payment;

    public function __construct()
    {
        $this->rideNumber = $this->generateRideNumber();
        $this->requestedAt = new \DateTime();
    }

    private function generateRideNumber()
    {
        return 'RIDE-' . strtoupper(bin2hex(random_bytes(6)));
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }


    public function getRideNumber()
    {
        return $this->rideNumber;
    }

    public function getRiderId()
    {
        return $this->riderId;
    }

    public function setRiderId(string $riderId)
    {
        $this->riderId = $riderId;
        return $this;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getRideType()
    {
        return $this->rideType;
    }

    public function setRideType(string $rideType)
    {
        $this->rideType = $rideType;
        return $this;
    }

    public function getPickupLatitude()
    {
        return $this->pickupLatitude;
    }

    public function setPickupLatitude(float $pickupLatitude)
    {
        $this->pickupLatitude = $pickupLatitude;
        return $this;
    }

    public function getPickupLongitude()
    {
        return $this->pickupLongitude;
    }

    public function setPickupLongitude(float $pickupLongitude)
    {
        $this->pickupLongitude = $pickupLongitude;
        return $this;
    }

    public function getPickupAddress()
    {
        return $this->pickupAddress;
    }

    public function setPickupAddress(string $pickupAddress)
    {
        $this->pickupAddress = $pickupAddress;
        return $this;
    }

    public function getDropoffLatitude()
    {
        return $this->dropoffLatitude;
    }

    public function setDropoffLatitude(?float $dropoffLatitude)
    {
        $this->dropoffLatitude = $dropoffLatitude;
        return $this;
    }

    public function getDropoffLongitude()
    {
        return $this->dropoffLongitude;
    }

    public function setDropoffLongitude(?float $dropoffLongitude)
    {
        $this->dropoffLongitude = $dropoffLongitude;
        return $this;
    }

    public function getDropoffAddress()
    {
        return $this->dropoffAddress;
    }

    public function setDropoffAddress(?string $dropoffAddress)
    {
        $this->dropoffAddress = $dropoffAddress;
        return $this;
    }

    public function getEstimatedPrice()
    {
        return $this->estimatedPrice;
    }

    public function setEstimatedPrice(float $estimatedPrice)
    {
        $this->estimatedPrice = $estimatedPrice;
        return $this;
    }

    public function getActualPrice()
    {
        return $this->actualPrice;
    }

    public function setActualPrice(?float $actualPrice)
    {
        $this->actualPrice = $actualPrice;
        return $this;
    }

    public function getEstimatedDuration()
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(int $estimatedDuration)
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }

    public function getActualDuration()
    {
        return $this->actualDuration;
    }

    public function setActualDuration(?int $actualDuration)
    {
        $this->actualDuration = $actualDuration;
        return $this;
    }

    public function getDistance()
    {
        return $this->distance;
    }

    public function setDistance(?int $distance)
    {
        $this->distance = $distance;
        return $this;
    }

    public function getRiderNotes()
    {
        return $this->riderNotes;
    }

    public function setRiderNotes(?string $riderNotes)
    {
        $this->riderNotes = $riderNotes;
        return $this;
    }

    public function getVehicle()
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getAcceptedAt()
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTime $acceptedAt)
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function getArrivedAt()
    {
        return $this->arrivedAt;
    }

    public function setArrivedAt(?\DateTime $arrivedAt)
    {
        $this->arrivedAt = $arrivedAt;
        return $this;
    }

    public function getStartedAt()
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt)
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getRequestedAt()
    {
        return $this->requestedAt;
    }

    public function getRiderRating()
    {
        return $this->riderRating;
    }

    public function setRiderRating(?float $riderRating)
    {
        $this->riderRating = $riderRating;
        return $this;
    }

    public function getDriverRating()
    {
        return $this->driverRating;
    }

    public function setDriverRating(?float $driverRating)
    {
        $this->driverRating = $driverRating;
        return $this;
    }

    public function getRiderReview()
    {
        return $this->riderReview;
    }

    public function setRiderReview(?string $riderReview)
    {
        $this->riderReview = $riderReview;
        return $this;
    }

    public function getDriverReview()
    {
        return $this->driverReview;
    }

    public function setDriverReview(?string $driverReview)
    {
        $this->driverReview = $driverReview;
        return $this;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment)
    {
        $this->payment = $payment;
        return $this;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isActive()
    {
        return in_array($this->status, [
            self::STATUS_REQUESTED,
            self::STATUS_ACCEPTED,
            self::STATUS_ARRIVED,
            self::STATUS_IN_PROGRESS
        ]);
    }
}
