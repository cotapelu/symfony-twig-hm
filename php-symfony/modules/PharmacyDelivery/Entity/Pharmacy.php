<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\PharmacyDelivery\Repository\PharmacyRepository::class)]
#[ORM\Table(name: "pharmacy_delivery_pharmacies")]
class Pharmacy
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BUSY = 'busy';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 200)]
    private string $name;

    #[ORM\Column(type: "string", length: 20)]
    private string $licenseNumber;

    #[ORM\Column(type: "string", length: 255)]
    private string $address;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $latitude;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $longitude;

    #[ORM\Column(type: "string", length: 20)]
    private string $phone;

    #[ORM\Column(type: "string", length: 100)]
    private string $email;

    #[ORM\Column(type: "string", length: 20)]
    private string $openingTime;

    #[ORM\Column(type: "string", length: 20)]
    private string $closingTime;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $deliveryFee;

    #[ORM\Column(type: "integer")]
    private int $estimatedDeliveryTime;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: "boolean")]
    private bool $is24Hours = false;

    #[ORM\Column(type: "boolean")]
    private bool $prescriptionRequired = true;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2)]
    private float $rating = 0;

    #[ORM\Column(type: "integer")]
    private int $totalReviews = 0;

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

    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getLicenseNumber() { return $this->licenseNumber; }
    public function setLicenseNumber(string $licenseNumber) { $this->licenseNumber = $licenseNumber; return $this; }
    public function getAddress() { return $this->address; }
    public function setAddress(string $address) { $this->address = $address; return $this; }
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(string $phone) { $this->phone = $phone; return $this; }
    public function getEmail() { return $this->email; }
    public function setEmail(string $email) { $this->email = $email; return $this; }
    public function getOpeningTime() { return $this->openingTime; }
    public function setOpeningTime(string $openingTime) { $this->openingTime = $openingTime; return $this; }
    public function getClosingTime() { return $this->closingTime; }
    public function setClosingTime(string $closingTime) { $this->closingTime = $closingTime; return $this; }
    public function getDeliveryFee() { return $this->deliveryFee; }
    public function setDeliveryFee(float $deliveryFee) { $this->deliveryFee = $deliveryFee; return $this; }
    public function getEstimatedDeliveryTime() { return $this->estimatedDeliveryTime; }
    public function setEstimatedDeliveryTime(int $estimatedDeliveryTime) { $this->estimatedDeliveryTime = $estimatedDeliveryTime; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function isActive() { return $this->status === self::STATUS_ACTIVE; }
    public function is24Hours() { return $this->is24Hours; }
    public function setIs24Hours(bool $is24Hours) { $this->is24Hours = $is24Hours; return $this; }
    public function isPrescriptionRequired() { return $this->prescriptionRequired; }
    public function setPrescriptionRequired(bool $prescriptionRequired) { $this->prescriptionRequired = $prescriptionRequired; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getTotalReviews() { return $this->totalReviews; }
    public function setTotalReviews(int $totalReviews) { $this->totalReviews = $totalReviews; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isOpenNow()
    {
        if ($this->is24Hours) return true;
        $now = new \DateTime();
        $currentTime = $now->format('H:i:s');
        return $currentTime >= $this->openingTime && $currentTime <= $this->closingTime;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'license_number' => $this->licenseNumber,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'opening_time' => $this->openingTime,
            'closing_time' => $this->closingTime,
            'delivery_fee' => $this->deliveryFee,
            'estimated_delivery_time' => $this->estimatedDeliveryTime,
            'status' => $this->status,
            'is_24_hours' => $this->is24Hours,
            'prescription_required' => $this->prescriptionRequired,
            'rating' => $this->rating,
            'total_reviews' => $this->totalReviews,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
