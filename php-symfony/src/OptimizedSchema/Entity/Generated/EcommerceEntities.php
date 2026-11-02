<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_products')]
#[ORM\Index(name: 'idx_ecommerce_products_seller_id', columns: ['seller_id'])]
#[ORM\Index(name: 'idx_ecommerce_products_category', columns: ['category'])]
#[ORM\Index(name: 'idx_ecommerce_products_is_active', columns: ['is_active'])]
class EcommerceProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'seller_id', type: 'uuid')]
    private string $sellerId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $category;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $images = [];

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $sellerId, string $name, int $priceCents)
    {
        $this->id = $id;
        $this->sellerId = $sellerId;
        $this->name = $name;
        $this->priceCents = $priceCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getSellerId(): string { return $this->sellerId; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getPriceCents(): int { return $this->priceCents; }
    public function setPriceCents(int $priceCents): self { $this->priceCents = $priceCents; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $stock): self { $this->stock = $stock; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getImages(): array { return $this->images; }
    public function setImages(array $images): self { $this->images = $images; return $this; }
    public function getMetadata(): array { return $this->metadata; }
    public function setMetadata(array $metadata): self { $this->metadata = $metadata; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_orders')]
#[ORM\Index(name: 'idx_ecommerce_orders_buyer_id', columns: ['buyer_id'])]
#[ORM\Index(name: 'idx_ecommerce_orders_product_id', columns: ['product_id'])]
#[ORM\Index(name: 'idx_ecommerce_orders_status', columns: ['status'])]
class EcommerceOrder
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'buyer_id', type: 'uuid', nullable: true)]
    private ?string $buyerId;

    #[ORM\Column(name: 'product_id', type: 'uuid', nullable: true)]
    private ?string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'shipping_address', type: 'text', nullable: true)]
    private ?string $shippingAddress;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, int $quantity, int $priceCents)
    {
        $this->id = $id;
        $this->quantity = $quantity;
        $this->priceCents = $priceCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getBuyerId(): ?string { return $this->buyerId; }
    public function setBuyerId(?string $buyerId): self { $this->buyerId = $buyerId; return $this; }
    public function getProductId(): ?string { return $this->productId; }
    public function setProductId(?string $productId): self { $this->productId = $productId; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPriceCents(): int { return $this->priceCents; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getShippingAddress(): ?string { return $this->shippingAddress; }
    public function setShippingAddress(?string $shippingAddress): self { $this->shippingAddress = $shippingAddress; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_transactions')]
#[ORM\Index(name: 'idx_ecommerce_transactions_order_id', columns: ['order_id'])]
#[ORM\Index(name: 'idx_ecommerce_transactions_status', columns: ['status'])]
class EcommerceTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'order_id', type: 'uuid', nullable: true)]
    private ?string $orderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 20, nullable: true)]
    private ?string $paymentMethod;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'transaction_id', type: 'string', length: 100, nullable: true)]
    private ?string $transactionId;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public const TYPE_PAYMENT = 'PAYMENT';
    public const TYPE_REFUND = 'REFUND';

    public function __construct(string $id, string $type, int $amountCents)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amountCents = $amountCents;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getOrderId(): ?string { return $this->orderId; }
    public function setOrderId(?string $orderId): self { $this->orderId = $orderId; return $this; }
    public function getType(): string { return $this->type; }
    public function getAmountCents(): int { return $this->amountCents; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getTransactionId(): ?string { return $this->transactionId; }
    public function setTransactionId(?string $transactionId): self { $this->transactionId = $transactionId; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}