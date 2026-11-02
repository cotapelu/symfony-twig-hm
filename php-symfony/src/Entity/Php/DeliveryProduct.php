<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_products')]
#[ORM\Index(name: 'idx_delivery_products_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_delivery_products_category', columns: ['category'])]
#[ORM\Index(name: 'idx_delivery_products_is_active', columns: ['is_active'])]
class DeliveryProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $storeId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $category;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $storeId, string $category, string $name, int $priceCents, int $stock)
    {
        $this->id = $id;
        $this->storeId = $storeId;
        $this->category = $category;
        $this->name = $name;
        $this->priceCents = $priceCents;
        $this->stock = $stock;
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

    public function getStock()
    {
        return $this->stock;
    }

    public function setStock(int $stock)
    {
        $this->stock = $stock;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
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

    public function isInStock()
    {
        return $this->isActive && $this->stock > 0;
    }

    public function isOutOfStock()
    {
        return !$this->isActive || $this->stock <= 0;
    }

    public function isLowStock()
    {
        return $this->isActive && $this->stock > 0 && $this->stock < 10;
    }
}
