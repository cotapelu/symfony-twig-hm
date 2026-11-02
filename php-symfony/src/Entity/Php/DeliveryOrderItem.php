<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.delivery_order_items')]
#[ORM\Index(name: 'idx_delivery_order_items_order_id', columns: ['order_id'])]
#[ORM\Index(name: 'idx_delivery_order_items_product_id', columns: ['product_id'])]
class DeliveryOrderItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $orderId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $notes = null;

    public function __construct(string $id, string $orderId, string $productId, int $quantity, int $priceCents)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->priceCents = $priceCents;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId(string $productId)
    {
        $this->productId = $productId;
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

    public function isAvailable()
    {
        return $this->quantity > 0;
    }

    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }
}
