<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_orders')]
#[ORM\Index(name: 'idx_ecommerce_orders_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_ecommerce_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_ecommerce_orders_created_at', columns: ['created_at'])]
class EcommerceOrder
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'integer')]
    private int $totalCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $billingAddress = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $customerId, int $totalCents)
    {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->totalCents = $totalCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
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

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?array $billingAddress)
    {
        $this->billingAddress = $billingAddress;
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
}
