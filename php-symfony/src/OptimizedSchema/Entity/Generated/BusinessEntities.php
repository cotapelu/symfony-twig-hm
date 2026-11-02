<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'businesses')]
#[ORM\Index(name: 'idx_businesses_owner_id', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_businesses_type', columns: ['type'])]
#[ORM\Index(name: 'idx_businesses_status', columns: ['status'])]
class Business
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'uuid')]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $license;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $ownerId, string $type, string $name)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getOwnerId(): string { return $this->ownerId; }
    public function getType(): string { return $this->type; }
    public function getName(): string { return $this->name; }
    public function getLicense(): ?string { return $this->license; }
    public function setLicense(?string $license): self { $this->license = $license; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'business_catalog')]
#[ORM\Index(name: 'idx_business_catalog_business_id', columns: ['business_id'])]
#[ORM\Index(name: 'idx_business_catalog_category', columns: ['category'])]
class BusinessCatalog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'business_id', type: 'uuid')]
    private string $businessId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $category;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $price = 0;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $businessId, string $name)
    {
        $this->id = $id;
        $this->businessId = $businessId;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getBusinessId(): string { return $this->businessId; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }
    public function getName(): string { return $this->name; }
    public function getPrice(): int { return $this->price; }
    public function setPrice(int $price): self { $this->price = $price; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $stock): self { $this->stock = $stock; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'service_bookings')]
#[ORM\Index(name: 'idx_service_bookings_provider_id', columns: ['provider_id'])]
#[ORM\Index(name: 'idx_service_bookings_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_service_bookings_status', columns: ['status'])]
class ServiceBooking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'provider_id', type: 'uuid', nullable: true)]
    private ?string $providerId;

    #[ORM\Column(name: 'customer_id', type: 'uuid', nullable: true)]
    private ?string $customerId;

    #[ORM\Column(name: 'service_type', type: 'string', length: 50)]
    private string $serviceType;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime', nullable: true)]
    private ?\DateTime $scheduledAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $data = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $serviceType)
    {
        $this->id = $id;
        $this->serviceType = $serviceType;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getProviderId(): ?string { return $this->providerId; }
    public function setProviderId(?string $providerId): self { $this->providerId = $providerId; return $this; }
    public function getCustomerId(): ?string { return $this->customerId; }
    public function setCustomerId(?string $customerId): self { $this->customerId = $customerId; return $this; }
    public function getServiceType(): string { return $this->serviceType; }
    public function getScheduledAt(): ?\DateTime { return $this->scheduledAt; }
    public function setScheduledAt(?\DateTime $scheduledAt): self { $this->scheduledAt = $scheduledAt; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'service_reviews')]
#[ORM\Index(name: 'idx_service_reviews_booking_id', columns: ['booking_id'])]
#[ORM\Index(name: 'idx_service_reviews_user_id', columns: ['user_id'])]
class ServiceReview
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'booking_id', type: 'uuid')]
    private string $bookingId;

    #[ORM\Column(name: 'user_id', type: 'uuid', nullable: true)]
    private ?string $userId;

    #[ORM\Column(type: 'integer')]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $bookingId, int $rating)
    {
        $this->id = $id;
        $this->bookingId = $bookingId;
        $this->rating = $rating;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getBookingId(): string { return $this->bookingId; }
    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(?string $userId): self { $this->userId = $userId; return $this; }
    public function getRating(): int { return $this->rating; }
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self { $this->comment = $comment; return $this; }
}