<?php

declare(strict_types=1);

namespace App\Business\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'business_catalog')]
#[ORM\HasLifecycleCallbacks]
class BusinessCatalog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Business::class)]
    #[ORM\JoinColumn(name: 'business_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Business $business;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $priceCents = null;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(Business $business, string $name)
    {
        $this->business = $business;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBusiness()
    {
        return $this->business;
    }

    public function setBusiness(Business $business)
    {
        $this->business = $business;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(?string $category)
    {
        $this->category = $category;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getPriceCents()
    {
        return $this->priceCents;
    }

    public function setPriceCents(?int $priceCents)
    {
        $this->priceCents = $priceCents;
        return $this;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function setStock(int $stock)
    {
        $this->stock = $stock;
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

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'businessId' => $this->business->getId(),
            'category' => $this->category,
            'name' => $this->name,
            'description' => $this->description,
            'priceCents' => $this->priceCents,
            'stock' => $this->stock,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
