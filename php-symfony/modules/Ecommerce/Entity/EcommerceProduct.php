<?php

declare(strict_types=1);

namespace App\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_products', indexes: [
    new Index(name: 'idx_product_seller', columns: ['sellerId']),
    new Index(name: 'idx_product_category', columns: ['category']),
    new Index(name: 'idx_product_active', columns: ['isActive']),
    new Index(name: 'idx_product_created', columns: ['createdAt'])
])]
class EcommerceProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $sellerId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $priceInCents = 0;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct(
        ?\DateTime $createdAt = null,
    )
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function setSellerId(string $sellerId)
    {
        $this->sellerId = $sellerId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getPrice()
    {
        return $this->priceInCents / 100;
    }

    public function setPrice(float $price)
    {
        $this->priceInCents = (int) round($price * 100);
    }

    public function getPriceInCents()
    {
        return $this->priceInCents;
    }

    public function setPriceInCents(int $priceInCents)
    {
        $this->priceInCents = $priceInCents;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function setStock(int $stock)
    {
        $this->stock = $stock;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages(?array $images)
    {
        $this->images = $images;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(?string $category)
    {
        $this->category = $category;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags(?array $tags)
    {
        $this->tags = $tags;
    }

    public function setAttributes(?array $attributes)
    {
        $this->tags = $attributes;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function activate(): self
    {
        $this->isActive = true;
        return $this;
    }

    public function deactivate(): self
    {
        $this->isActive = false;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'sellerId' => $this->sellerId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->getPrice(),
            'priceInCents' => $this->priceInCents,
            'currency' => $this->currency,
            'stock' => $this->stock,
            'images' => $this->images,
            'category' => $this->category,
            'tags' => $this->tags,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
