<?php

declare(strict_types=1);

namespace App\RideHailing\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\RideHailing\Repository\DriverRepository::class)
 * @ORM\Table(name="ride_hailing_drivers")
 */
class Driver
{
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ONLINE = 'online';
    public const STATUS_BUSY = 'busy';
    public const STATUS_RIDE_REQUEST = 'ride_request';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private string $userId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $licensePlate;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $vehicleType;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $vehicleBrand;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $vehicleModel;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $vehicleColor;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $vehicleYear = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $status = self::STATUS_OFFLINE;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6)
     */
    private float $currentLatitude;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=6)
     */
    private float $currentLongitude;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private float $rating = 5.00;

    /**
     * @ORM\Column(type="integer")
     */
    private int $totalRides = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $completedRides = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $cancelledRides = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isVerified = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isActive = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $lastActiveAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $documents = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $bankAccountNumber = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $bankName = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $bankAccountName = null;

    public function __construct()
    {
        $this->lastActiveAt = new \DateTime();
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


    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getLicensePlate()
    {
        return $this->licensePlate;
    }

    public function setLicensePlate(string $licensePlate)
    {
        $this->licensePlate = $licensePlate;
        return $this;
    }

    public function getVehicleType()
    {
        return $this->vehicleType;
    }

    public function setVehicleType(string $vehicleType)
    {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    public function getVehicleBrand()
    {
        return $this->vehicleBrand;
    }

    public function setVehicleBrand(string $vehicleBrand)
    {
        $this->vehicleBrand = $vehicleBrand;
        return $this;
    }

    public function getVehicleModel()
    {
        return $this->vehicleModel;
    }

    public function setVehicleModel(string $vehicleModel)
    {
        $this->vehicleModel = $vehicleModel;
        return $this;
    }

    public function getVehicleColor()
    {
        return $this->vehicleColor;
    }

    public function setVehicleColor(string $vehicleColor)
    {
        $this->vehicleColor = $vehicleColor;
        return $this;
    }

    public function getVehicleYear()
    {
        return $this->vehicleYear;
    }

    public function setVehicleYear(?int $vehicleYear)
    {
        $this->vehicleYear = $vehicleYear;
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

    public function getCurrentLatitude()
    {
        return $this->currentLatitude;
    }

    public function setCurrentLatitude(float $currentLatitude)
    {
        $this->currentLatitude = $currentLatitude;
        return $this;
    }

    public function getCurrentLongitude()
    {
        return $this->currentLongitude;
    }

    public function setCurrentLongitude(float $currentLongitude)
    {
        $this->currentLongitude = $currentLongitude;
        return $this;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating(float $rating)
    {
        $this->rating = $rating;
        return $this;
    }

    public function getTotalRides()
    {
        return $this->totalRides;
    }

    public function setTotalRides(int $totalRides)
    {
        $this->totalRides = $totalRides;
        return $this;
    }

    public function getCompletedRides()
    {
        return $this->completedRides;
    }

    public function setCompletedRides(int $completedRides)
    {
        $this->completedRides = $completedRides;
        return $this;
    }

    public function getCancelledRides()
    {
        return $this->cancelledRides;
    }

    public function setCancelledRides(int $cancelledRides)
    {
        $this->cancelledRides = $cancelledRides;
        return $this;
    }

    public function isVerified()
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified)
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(?\DateTime $lastActiveAt)
    {
        $this->lastActiveAt = $lastActiveAt;
        return $this;
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function setDocuments(?string $documents)
    {
        $this->documents = $documents;
        return $this;
    }

    public function getBankAccountNumber()
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(?string $bankAccountNumber)
    {
        $this->bankAccountNumber = $bankAccountNumber;
        return $this;
    }

    public function getBankName()
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName)
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function getBankAccountName()
    {
        return $this->bankAccountName;
    }

    public function setBankAccountName(?string $bankAccountName)
    {
        $this->bankAccountName = $bankAccountName;
        return $this;
    }

public function isOnline()
{
    return $this->status !== self::STATUS_OFFLINE;
}

    public function isAvailable()
    {
        return $this->status === self::STATUS_ONLINE && $this->isActive;
    }
}
