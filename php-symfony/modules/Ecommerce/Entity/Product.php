<?php

namespace App\Ecommerce\Entity;

use App\Attribute\Module;

#[Module(name: 'Ecommerce', dependencies: ['User', 'Product', 'Order'])]
class Product
{
    private ?string $id = null;
    private int $sellerId;
    private string $name;
    private ?string $description = null;
    private float $price;
    private ?float $salePrice = null;
    private string $currency;
    private int $stock;
    private string $category;
    private array $images = [];
    private array $attributes = [];
    private string $status; // active, inactive, out_of_stock
    private \DateTime $createdAt;

    public function __construct(int $sellerId, string $name, float $price, string $category)
    {
        $this->sellerId = $sellerId;
        $this->name = $name;
        $this->price = $price;
        $this->category = $category;
        $this->currency = 'VND';
        $this->stock = 0;
        $this->status = 'inactive';
        $this->createdAt = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function setId(string $id) { $this->id = $id; return $this; }
    public function getSellerId() { return $this->sellerId; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPrice() { return $this->price; }
    public function getSalePrice() { return $this->salePrice; }
    public function getCurrency() { return $this->currency; }
    public function getStock() { return $this->stock; }
    public function getCategory() { return $this->category; }
    public function getImages() { return $this->images; }
    public function getAttributes() { return $this->attributes; }
    public function getStatus() { return $this->status; }

    public function setDescription(?string $desc) { $this->description = $desc; return $this; }
    public function setSalePrice(?float $price) { $this->salePrice = $price; return $this; }
    public function setStock(int $qty) { $this->stock = $qty; return $this; }
    public function setImages(array $images) { $this->images = $images; return $this; }
    public function setAttributes(array $attrs) { $this->attributes = $attrs; return $this; }
    public function activate() { $this->status = 'active'; return $this; }
    public function deactivate() { $this->status = 'inactive'; return $this; }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'sellerId' => $this->getSellerId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'price' => $this->getPrice(),
            'salePrice' => $this->getSalePrice(),
            'currency' => $this->getCurrency(),
            'stock' => $this->getStock(),
            'category' => $this->getCategory(),
            'images' => $this->getImages(),
            'attributes' => $this->getAttributes(),
            'status' => $this->getStatus(),
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
