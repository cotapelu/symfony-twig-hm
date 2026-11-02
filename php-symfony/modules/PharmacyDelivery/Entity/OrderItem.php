<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "pharmacy_delivery_order_items")]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: "items")]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: Medicine::class)]
    #[ORM\JoinColumn(name: "medicine_id", referencedColumnName: "id")]
    private Medicine $medicine;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $unitPrice;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $totalPrice;

    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getOrder() { return $this->order; }
    public function setOrder(Order $order) { $this->order = $order; return $this; }
    public function getMedicine() { return $this->medicine; }
    public function setMedicine(Medicine $medicine) { $this->medicine = $medicine; return $this; }
    public function getQuantity() { return $this->quantity; }
    public function setQuantity(int $quantity) { $this->quantity = $quantity; return $this; }
    public function getUnitPrice() { return $this->unitPrice; }
    public function setUnitPrice(float $unitPrice) { $this->unitPrice = $unitPrice; return $this; }
    public function getTotalPrice() { return $this->totalPrice; }
    public function setTotalPrice(float $totalPrice) { $this->totalPrice = $totalPrice; return $this; }
}
