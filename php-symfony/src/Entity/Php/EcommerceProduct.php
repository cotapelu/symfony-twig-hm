<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_products')]
#[ORM\Index(name: 'idx_ecommerce_products_business_id', columns: ['business_id'])]
#[ORM\Index(name: 'idx_ecommerce_products_category', columns: ['category'])]
#[ORM\Index(name: 'idx_ecommerce_products_status', columns: ['status'])]
class EcommerceProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $businessId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $category;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stockQuantity = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attributes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $businessId, string $name, string $category, int $priceCents)
    {
        $this->id = $id;
        $this->businessId = $businessId;
        $this->name = $name;
        $this->category = $category;
        $this->priceCents = $priceCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBusinessId()
    {
        return $this->businessId;
    }

    public function setBusinessId(string $businessId)
    {
        $this->businessId = $businessId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
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

    public function getPriceCents()
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents)
    {
        $this->priceCents = $priceCents;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity)
    {
        $this->stockQuantity = $stockQuantity;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setAttributes(?array $attributes)
    {
        $this->attributes = $attributes;
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

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isInactive()
    {
        return $this->status === 'INACTIVE';
    }

    public function isOutOfStock()
    {
        return $this->stockQuantity <= 0;
    }

    public function isLowStock(int $threshold = 10)
    {
        return $this->stockQuantity > 0 && $this->stockQuantity <= $threshold;
    }

    public function decreaseStock(int $quantity)
    {
        $this->stockQuantity = max(0, $this->stockQuantity - $quantity);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function increaseStock(int $quantity)
    {
        $this->stockQuantity += $quantity;
        $this->updatedAt = new \DateTime();
        return $this;
    }
}
