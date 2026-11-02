<?php

declare(strict_types=1);

namespace App\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_orders', indexes: [
    new Index(name: 'idx_order_user', columns: ['userId']),
    new Index(name: 'idx_order_product', columns: ['productId']),
    new Index(name: 'idx_order_status', columns: ['status']),
    new Index(name: 'idx_order_created', columns: ['createdAt'])
])]
class EcommerceOrder
{
    /**
     * UUID identifier - read-only property.
     * This ID is generated upon object creation and should not be modified.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 1;

    #[ORM\Column(type: 'integer')]
    private int $totalPriceInCents = 0;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'pending';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

     public function __construct(
         ?\DateTime $createdAt = null,
     )
     {
         $this->createdAt = $createdAt ?? new \DateTime();
     }

     public function setId(string $id)
     {
         $this->id = $id;
         return $this;
     }

     public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId(string $productId)
    {
        $this->productId = $productId;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    public function getTotalPrice()
    {
        return $this->totalPriceInCents / 100.0;
    }

    public function setTotalPrice(float $totalPrice)
    {
        $this->totalPriceInCents = (int) round($totalPrice * 100);
    }

    public function getTotalPriceInCents()
    {
        return $this->totalPriceInCents;
    }

    public function setTotalPriceInCents(int $totalPriceInCents)
    {
        $this->totalPriceInCents = $totalPriceInCents;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'totalPrice' => $this->getTotalPrice(),
            'totalPriceInCents' => $this->totalPriceInCents,
            'currency' => $this->currency,
            'shippingAddress' => $this->shippingAddress,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
