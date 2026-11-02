<?php

declare(strict_types=1);

namespace App\Delivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_order_items')]
class DeliveryOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: DeliveryOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private DeliveryOrder $order;

    #[ORM\ManyToOne(targetEntity: DeliveryProduct::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private DeliveryProduct $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(DeliveryOrder $order, DeliveryProduct $product, int $quantity, int $priceCents)
    {
        $this->order = $order;
        $this->product = $product;
        $this->quantity = $quantity;
        $this->priceCents = $priceCents;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder(DeliveryOrder $order)
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct(DeliveryProduct $product)
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

    public function getPriceCents()
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents)
    {
        $this->priceCents = $priceCents;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTotalCents()
    {
        return $this->quantity * $this->priceCents;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order->getId(),
            'productId' => $this->product->getId(),
            'quantity' => $this->quantity,
            'priceCents' => $this->priceCents,
            'totalCents' => $this->getTotalCents(),
            'notes' => $this->notes,
        ];
    }
}
