<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: "grocery_delivery_payments")]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const METHOD_CASH = 'cash';
    public const METHOD_WALLET = 'wallet';
    public const METHOD_CARD = 'card';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    /** @readonly */
    private int $id;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: "payment")]
    private ?Order $order = null;

    #[ORM\Column(type: "string", length: 36)]
    private string $transactionId;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(type: "string", length: 20)]
    private string $currency = 'VND';

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: "string", length: 20)]
    private string $paymentMethod = self::METHOD_CASH;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $platformFee = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $storeEarning = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $driverEarning = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $paidAt = null;

    public function __construct()
    {
        $this->transactionId = 'GD-PAY-' . strtoupper(bin2hex(random_bytes(8)));
        $this->createdAt = new \DateTime();
    }

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
    public function setOrder(?Order $order) { $this->order = $order; return $this; }
    public function getTransactionId() { return $this->transactionId; }
    public function getAmount() { return $this->amount; }
    public function setAmount(float $amount) { $this->amount = $amount; return $this; }
    public function getCurrency() { return $this->currency; }
    public function setCurrency(string $currency) { $this->currency = $currency; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function setPaymentMethod(string $paymentMethod) { $this->paymentMethod = $paymentMethod; return $this; }
    public function getPlatformFee() { return $this->platformFee; }
    public function setPlatformFee(?float $platformFee) { $this->platformFee = $platformFee; return $this; }
    public function getStoreEarning() { return $this->storeEarning; }
    public function setStoreEarning(?float $storeEarning) { $this->storeEarning = $storeEarning; return $this; }
    public function getDriverEarning() { return $this->driverEarning; }
    public function setDriverEarning(?float $driverEarning) { $this->driverEarning = $driverEarning; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getPaidAt() { return $this->paidAt; }
    public function setPaidAt(?\DateTime $paidAt) { $this->paidAt = $paidAt; return $this; }
    public function isCompleted() { return $this->status === self::STATUS_COMPLETED; }
}
