<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\PharmacyDelivery\Repository\DeliveryPersonRepository::class)]
#[ORM\Table(name: "pharmacy_delivery_delivery_persons")]
class DeliveryPerson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 36)]
    private string $userId;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 20)]
    private string $phone;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $vehicleType;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $vehicleNumber = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = 'available';

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $latitude;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $longitude;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $rating = 5.0;

    #[ORM\Column(type: "integer")]
    private int $totalDeliveries = 0;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
    public function setPhone(string $phone) { $this->phone = $phone; return $this; }
    public function getEmail() { return $this->email; }
    public function setEmail(?string $email) { $this->email = $email; return $this; }
    public function getVehicleType() { return $this->vehicleType; }
    public function setVehicleType(string $vehicleType) { $this->vehicleType = $vehicleType; return $this; }
    public function getVehicleNumber() { return $this->vehicleNumber; }
    public function setVehicleNumber(?string $vehicleNumber) { $this->vehicleNumber = $vehicleNumber; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getTotalDeliveries() { return $this->totalDeliveries; }
    public function setTotalDeliveries(int $totalDeliveries) { $this->totalDeliveries = $totalDeliveries; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isAvailable() { return $this->status === 'available'; }
}
