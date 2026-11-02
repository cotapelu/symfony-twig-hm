<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_orders')]
#[ORM\Index(name: 'idx_delivery_orders_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_delivery_orders_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_delivery_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_delivery_orders_created_at', columns: ['created_at'])]
class DeliveryOrder
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $storeId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

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

    public function __construct(string $id, string $storeId, string $customerId, string $type, int $totalCents)
    {
        $this->id = $id;
        $this->storeId = $storeId;
        $this->customerId = $customerId;
        $this->type = $type;
        $this->totalCents = $totalCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function setStoreId(string $storeId)
    {
        $this->storeId = $storeId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId)
    {
        $this->customerId = $customerId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?array $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDeliveryPersonId()
    {
        return $this->deliveryPersonId;
    }

    public function setDeliveryPersonId(?string $deliveryPersonId)
    {
        $this->deliveryPersonId = $deliveryPersonId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTotalCents()
    {
        return $this->totalCents;
    }

    public function setTotalCents(int $totalCents)
    {
        $this->totalCents = $totalCents;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
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
        return $this->status === 'PENDING';
    }

    public function isConfirmed()
    {
        return $this->status === 'CONFIRMED';
    }

    public function isProcessing()
    {
        return $this->status === 'PROCESSING';
    }

    public function isDelivered()
    {
        return $this->status === 'DELIVERED';
    }

    public function isCancelled()
    {
        return $this->status === 'CANCELLED';
    }

    public function isRefunded()
    {
        return $this->status === 'REFUNDED';
    }

    public function isRestaurant()
    {
        return $this->type === 'RESTAURANT';
    }

    public function isPharmacy()
    {
        return $this->type === 'PHARMACY';
    }

    public function isGrocery()
    {
        return $this->type === 'GROCERY';
    }

    public function hasDeliveryPerson()
    {
        return $this->deliveryPersonId !== null;
    }
}
