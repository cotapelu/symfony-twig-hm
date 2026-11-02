<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use App\OptimizedSchema\Entity\Trait\StatusAwareTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_orders')]
#[ORM\Index(name: 'idx_delivery_orders_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_delivery_orders_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_delivery_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_delivery_orders_created_at', columns: ['created_at'])]
class DeliveryOrder
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use StatusAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'store_id', type: 'uuid', nullable: true)]
    private ?string $storeId;

    #[ORM\Column(name: 'customer_id', type: 'uuid', nullable: true)]
    private ?string $customerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'delivery_address', type: 'text', nullable: true)]
    private ?string $deliveryAddress;

    #[ORM\Column(name: 'delivery_person_id', type: 'uuid', nullable: true)]
    private ?string $deliveryPersonId;

    #[ORM\Column(type: 'integer')]
    private int $total;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const TYPE_FOOD = 'FOOD';
    public const TYPE_PHARMACY = 'PHARMACY';
    public const TYPE_GROCERY = 'GROCERY';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_DELIVERING = 'DELIVERING';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public function __construct(string $id, string $type, int $total)
    {
        $this->id = $id;
        $this->type = $type;
        $this->total = $total;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
    }

    public function getStoreId(): ?string
    {
        return $this->storeId;
    }

    public function setStoreId(?string $storeId): self
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getDeliveryPersonId(): ?string
    {
        return $this->deliveryPersonId;
    }

    public function setDeliveryPersonId(?string $deliveryPersonId): self
    {
        $this->deliveryPersonId = $deliveryPersonId;
        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}