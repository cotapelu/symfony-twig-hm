<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_products')]
#[ORM\Index(name: 'idx_ecom_products_seller', columns: ['seller_id'])]
#[ORM\Index(name: 'idx_ecom_products_category', columns: ['category'])]
#[ORM\Index(name: 'idx_ecom_products_active', columns: ['is_active'])]
#[HasLifecycleCallbacks]
class EcommerceProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'seller_id', type: 'string', length: 36)]
    private string $sellerId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $images = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $sellerId, string $name, int $priceCents)
    {
        $this->id = $id;
        $this->sellerId = $sellerId;
        $this->name = $name;
        $this->priceCents = $priceCents;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getSellerId() { return $this->sellerId; }
    public function getCategory() { return $this->category; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPriceCents() { return $this->priceCents; }
    public function getStock() { return $this->stock; }
    public function isActive() { return $this->isActive; }
    public function getImages() { return $this->images; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setCategory(?string $cat) { $this->category = $cat; return $this; }
    public function setStock(int $stock) { $this->stock = $stock; return $this; }
    public function decreaseStock(int $qty) { $this->stock -= $qty; return $this; }
    public function setIsActive(bool $active) { $this->isActive = $active; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_orders')]
#[ORM\Index(name: 'idx_ecom_orders_buyer', columns: ['buyer_id'])]
#[ORM\Index(name: 'idx_ecom_orders_product', columns: ['product_id'])]
#[ORM\Index(name: 'idx_ecom_orders_status', columns: ['status'])]
#[HasLifecycleCallbacks]
class EcommerceOrder
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_SHIPPING = 'SHIPPING';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'buyer_id', type: 'string', length: 36)]
    private string $buyerId;

    #[ORM\Column(name: 'product_id', type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'shipping_address', type: 'text')]
    private string $shippingAddress;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $buyerId, string $productId, int $quantity, int $priceCents, string $address)
    {
        $this->id = $id;
        $this->buyerId = $buyerId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->priceCents = $priceCents;
        $this->shippingAddress = $address;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getBuyerId() { return $this->buyerId; }
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
    public function getPriceCents() { return $this->priceCents; }
    public function getStatus() { return $this->status; }
    public function getShippingAddress() { return $this->shippingAddress; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_transactions')]
#[ORM\Index(name: 'idx_ecom_tx_order', columns: ['order_id'])]
#[ORM\Index(name: 'idx_ecom_tx_status', columns: ['status'])]
class EcommerceTransaction
{
    public const TYPE_PAYMENT = 'PAYMENT';
    public const TYPE_REFUND = 'REFUND';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'order_id', type: 'string', length: 36)]
    private string $orderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'transaction_id', type: 'string', length: 100, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $orderId, string $type, int $amountCents)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->type = $type;
        $this->amountCents = $amountCents;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getOrderId() { return $this->orderId; }
    public function getType() { return $this->type; }
    public function getAmountCents() { return $this->amountCents; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function getStatus() { return $this->status; }
    public function getTransactionId() { return $this->transactionId; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function setTransactionId(?string $txId) { $this->transactionId = $txId; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.wallets')]
#[ORM\Index(name: 'idx_wallets_user', columns: ['user_id'], unique: true)]
class Wallet
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_BLOCKED = 'BLOCKED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'balance_cents', type: 'integer')]
    private int $balanceCents = 0;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'VND';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getBalanceCents() { return $this->balanceCents; }
    public function getCurrency() { return $this->currency; }
    public function getStatus() { return $this->status; }
    public function getVersion() { return $this->version; }

    public function credit(int $cents)
    {
        $this->balanceCents += $cents;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function debit(int $cents)
    {
        $this->balanceCents -= $cents;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function canDebit(int $cents)
    {
        return $this->balanceCents >= $cents && $this->status === self::STATUS_ACTIVE;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.wallet_transactions')]
#[ORM\Index(name: 'idx_wallet_tx_wallet', columns: ['wallet_id'])]
#[ORM\Index(name: 'idx_wallet_tx_created', columns: ['created_at'])]
#[ORM\Index(name: 'idx_wallet_tx_idempotency', columns: ['idempotency_key'])]
class WalletTransaction
{
    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAW = 'WITHDRAW';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'wallet_id', type: 'string', length: 36)]
    private string $walletId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'balance_after', type: 'integer')]
    private int $balanceAfter;

    #[ORM\Column(name: 'reference_id', type: 'string', length: 100, nullable: true)]
    private ?string $referenceId = null;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 100, nullable: true)]
    private ?string $idempotencyKey = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $walletId, string $type, int $amountCents, int $balanceAfter)
    {
        $this->id = $id;
        $this->walletId = $walletId;
        $this->type = $type;
        $this->amountCents = $amountCents;
        $this->balanceAfter = $balanceAfter;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getWalletId() { return $this->walletId; }
    public function getType() { return $this->type; }
    public function getAmountCents() { return $this->amountCents; }
    public function getBalanceAfter() { return $this->balanceAfter; }
    public function getReferenceId() { return $this->referenceId; }
    public function getIdempotencyKey() { return $this->idempotencyKey; }
    public function getStatus() { return $this->status; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.idempotency_keys')]
#[ORM\Index(name: 'idx_idempotency_key', columns: ['key_hash'])]
class IdempotencyKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'key_hash', type: 'string', length: 64)]
    private string $keyHash;

    #[ORM\Column(type: 'json')]
    private array $response;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $keyHash, array $response)
    {
        $this->id = $id;
        $this->keyHash = $keyHash;
        $this->response = $response;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getKeyHash() { return $this->keyHash; }
    public function getResponse() { return $this->response; }
    public function getCreatedAt() { return $this->createdAt; }
}