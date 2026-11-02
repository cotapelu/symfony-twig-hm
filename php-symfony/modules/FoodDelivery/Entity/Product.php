<?php

declare(strict_types=1);

namespace App\FoodDelivery\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\FoodDelivery\Repository\ProductRepository::class)]
#[ORM\Table(name: "food_delivery_products")]
class Product
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    /** @phpstan-readonly */
    private int $id;

    #[ORM\ManyToOne(targetEntity: Restaurant::class, inversedBy: "products")]
    #[ORM\JoinColumn(name: "restaurant_id", referencedColumnName: "id")]
    private Restaurant $restaurant;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $originalPrice = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $category;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $preparationTime = null;

    #[ORM\Column(type: "boolean")]
    private bool $isPopular = false;

    #[ORM\Column(type: "boolean")]
    private bool $isNew = false;

    #[ORM\OneToMany(mappedBy: "product", targetEntity: OrderItem::class)]
    /** @phpstan-readonly */
    private Collection $orderItems;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->orderItems = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }


    public function getRestaurant()
    {
        return $this->restaurant;
    }

    public function setRestaurant(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
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

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice(float $price)
    {
        $this->price = $price;
        return $this;
    }

    public function getOriginalPrice()
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?float $originalPrice)
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
        return $this;
    }

    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl)
    {
        $this->imageUrl = $imageUrl;
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

    public function getPreparationTime()
    {
        return $this->preparationTime;
    }

    public function setPreparationTime(?int $preparationTime)
    {
        $this->preparationTime = $preparationTime;
        return $this;
    }

    public function isPopular()
    {
        return $this->isPopular;
    }

    public function setPopular(bool $isPopular)
    {
        $this->isPopular = $isPopular;
        return $this;
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function setNew(bool $isNew)
    {
        $this->isNew = $isNew;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }


    public function getOrderItems()
    {
        return $this->orderItems;
    }
    public function getDiscountPercentage()
    {
        if (!$this->originalPrice || $this->originalPrice <= $this->price) {
            return 0;
        }
        return (int) (($this->originalPrice - $this->price) / $this->originalPrice * 100);
    }
    public function setId(int $id) { $this->id = $id; return $this; }
}
