<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: "grocery_delivery_stores")]
class Store
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    /** @readonly */
    private int $id;

    #[ORM\Column(type: "string", length: 36)]
    private string $ownerId;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $address;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $latitude;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $longitude;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $minOrderAmount = 0;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $deliveryFee = 0;

    #[ORM\Column(type: "integer")]
    private int $deliveryTime = 30;

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

    public function getOwnerId() { return $this->ownerId; }
    public function setOwnerId(string $ownerId) { $this->ownerId = $ownerId; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getAddress() { return $this->address; }
    public function setAddress(string $address) { $this->address = $address; return $this; }
    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; return $this; }
    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(?string $category) { $this->category = $category; return $this; }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; return $this; }
    public function getMinOrderAmount() { return $this->minOrderAmount; }
    public function setMinOrderAmount(float $minOrderAmount) { $this->minOrderAmount = $minOrderAmount; return $this; }
    public function getDeliveryFee() { return $this->deliveryFee; }
    public function setDeliveryFee(float $deliveryFee) { $this->deliveryFee = $deliveryFee; return $this; }
    public function getDeliveryTime() { return $this->deliveryTime; }
    public function setDeliveryTime(int $deliveryTime) { $this->deliveryTime = $deliveryTime; return $this; }
    public function getRating() { return $this->rating; }
    public function setRating(float $rating) { $this->rating = $rating; return $this; }
    public function getTotalReviews() { return $this->totalReviews; }
    public function setTotalReviews(int $totalReviews) { $this->totalReviews = $totalReviews; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isActive() { return $this->status === self::STATUS_ACTIVE; }
}
