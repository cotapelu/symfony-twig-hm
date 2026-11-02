<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: "grocery_delivery_persons")]
class DeliveryPerson
{
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ONLINE = 'online';
    public const STATUS_BUSY = 'busy';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 36)]
    private string $userId;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $vehicleType;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $licensePlate = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $status = self::STATUS_OFFLINE;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $currentLatitude = 0;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $currentLongitude = 0;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2)]
    private float $rating = 5.00;

    #[ORM\Column(type: "integer")]
    private int $completedDeliveries = 0;

    #[ORM\Column(type: "boolean")]
    private bool $isActive = true;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $lastActiveAt = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->lastActiveAt = new \DateTime();
    }

    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; return $this; }
    public function getVehicleType() { return $this->vehicleType; }
    public function setVehicleType(string $vehicleType) { $this->vehicleType = $vehicleType; return $this; }
    public function getLicensePlate() { return $this->licensePlate; }
    public function setLicensePlate(?string $licensePlate) { $this->licensePlate = $licensePlate; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getCurrentLatitude() { return $this->currentLatitude; }
    public function setCurrentLatitude(float $currentLatitude) { $this->currentLatitude = $currentLatitude; return $this; }
    public function getCurrentLongitude() { return $this->currentLongitude; }
    public function setCurrentLongitude(float $currentLongitude) { $this->currentLongitude = $currentLongitude; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getCompletedDeliveries() { return $this->completedDeliveries; }
    public function setCompletedDeliveries(int $completedDeliveries) { $this->completedDeliveries = $completedDeliveries; return $this; }
    public function isActive() { return $this->isActive; }
    public function setActive(bool $isActive) { $this->isActive = $isActive; return $this; }
    public function getLastActiveAt() { return $this->lastActiveAt; }
    public function setLastActiveAt(?\DateTime $lastActiveAt) { $this->lastActiveAt = $lastActiveAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isOnline() { return $this->status === self::STATUS_ONLINE; }
    public function isAvailable() { return $this->status === self::STATUS_ONLINE && $this->isActive; }
}
