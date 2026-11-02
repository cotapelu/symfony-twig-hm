<?php

declare(strict_types=1);

namespace App\Maps\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'places')]
class Place
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private float $latitude;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private float $longitude;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $placeType = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reviewCount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; }

    public function getAddress() { return $this->address; }
    public function setAddress(?string $address) { $this->address = $address; }

    public function getLatitude() { return $this->latitude; }
    public function setLatitude(float $latitude) { $this->latitude = $latitude; }

    public function getLongitude() { return $this->longitude; }
    public function setLongitude(float $longitude) { $this->longitude = $longitude; }

    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; }

    public function getPlaceType() { return $this->placeType; }
    public function setPlaceType(?string $placeType) { $this->placeType = $placeType; }

    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; }

    public function getRating() { return $this->rating; }
    public function setRating(?float $rating) { $this->rating = $rating; }

    public function getReviewCount() { return $this->reviewCount; }
    public function setReviewCount(?int $reviewCount) { $this->reviewCount = $reviewCount; }

    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; }

    public function getMetadata() { return $this->metadata; }
    public function setMetadata(?array $metadata) { $this->metadata = $metadata; }

    public function getCreatedAt() { return $this->createdAt; }
}
