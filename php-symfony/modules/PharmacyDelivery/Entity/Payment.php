<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\PharmacyDelivery\Repository\PaymentRepository::class)]
#[ORM\Table(name: "pharmacy_delivery_payments")]
class Payment
{
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_WALLET = 'wallet';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: "order_id", referencedColumnName: "id")]
    private Order $order;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(type: "string", length: 20)]
    private string $paymentMethod = self::METHOD_CASH;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $platformFee = 0;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $pharmacyEarning = 0;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $driverEarning = 0;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $paymentResponse = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $paidAt = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
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
    public function setOrder(Order $order) { $this->order = $order; return $this; }
    public function getAmount() { return $this->amount; }
    public function setAmount(float $amount) { $this->amount = $amount; return $this; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function setPaymentMethod(string $paymentMethod) { $this->paymentMethod = $paymentMethod; return $this; }
    public function getTransactionId() { return $this->transactionId; }
    public function setTransactionId(?string $transactionId) { $this->transactionId = $transactionId; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getPlatformFee() { return $this->platformFee; }
    public function setPlatformFee(float $platformFee) { $this->platformFee = $platformFee; return $this; }
    public function getPharmacyEarning() { return $this->pharmacyEarning; }
    public function setPharmacyEarning(float $pharmacyEarning) { $this->pharmacyEarning = $pharmacyEarning; return $this; }
    public function getDriverEarning() { return $this->driverEarning; }
    public function setDriverEarning(float $driverEarning) { $this->driverEarning = $driverEarning; return $this; }
    public function getPaymentResponse() { return $this->paymentResponse; }
    public function setPaymentResponse(?string $paymentResponse) { $this->paymentResponse = $paymentResponse; return $this; }
    public function getPaidAt() { return $this->paidAt; }
    public function setPaidAt(?\DateTime $paidAt) { $this->paidAt = $paidAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}
