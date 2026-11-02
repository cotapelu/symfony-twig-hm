<?php

declare(strict_types=1);

namespace App\FoodDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\FoodDelivery\Repository\OrderItemRepository::class)]
#[ORM\Table(name: "food_delivery_order_items")]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    /** @readonly */
    private int $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: "items")]
    #[ORM\JoinColumn(name: "order_id", referencedColumnName: "id")]
    private ?Order $order;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "id")]
    private Product $product;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $unitPrice;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $totalPrice;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $specialInstructions;

    public function getId()
    {
        return $this->id;
    }
    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }


    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder(?Order $order)
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice)
    {
        $this->unitPrice = $unitPrice;
        $this->recalculateTotal();
        return $this;
    }

    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice)
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getSpecialInstructions()
    {
        return $this->specialInstructions;
    }

    public function setSpecialInstructions(?string $specialInstructions)
    {
        $this->specialInstructions = $specialInstructions;
        return $this;
    }

    private function recalculateTotal()
    {
        $this->totalPrice = $this->unitPrice * $this->quantity;
    }
}
