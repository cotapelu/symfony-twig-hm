<?php

declare(strict_types=1);

namespace App\Delivery\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_orders')]
#[ORM\HasLifecycleCallbacks]
class DeliveryOrder
{
    public const TYPE_FOOD = 'FOOD';
    public const TYPE_PHARMACY = 'PHARMACY';
    public const TYPE_GROCERY = 'GROCERY';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: DeliveryStore::class)]
    #[ORM\JoinColumn(name: 'store_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private DeliveryStore $store;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $customer;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deliveryAddress = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $deliveryPersonId = null;

    #[ORM\Column(type: 'integer')]
    private int $totalCents;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(DeliveryStore $store, User $customer, string $type, int $totalCents)
    {
        $this->store = $store;
        $this->customer = $customer;
        $this->type = $type;
        $this->totalCents = $totalCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function setStore(DeliveryStore $store)
    {
        $this->store = $store;
        return $this;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer(User $customer)
    {
        $this->customer = $customer;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
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

    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?array $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getDeliveryPersonId()
    {
        return $this->deliveryPersonId;
    }

    public function setDeliveryPersonId(?string $deliveryPersonId)
    {
        $this->deliveryPersonId = $deliveryPersonId;
        return $this;
    }

    public function getTotalCents()
    {
        return $this->totalCents;
    }

    public function setTotalCents(int $totalCents)
    {
        $this->totalCents = $totalCents;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
        ]);
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'storeId' => $this->store->getId(),
            'customerId' => $this->customer->getId(),
            'type' => $this->type,
            'status' => $this->status,
            'deliveryAddress' => $this->deliveryAddress,
            'deliveryPersonId' => $this->deliveryPersonId,
            'totalCents' => $this->totalCents,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
