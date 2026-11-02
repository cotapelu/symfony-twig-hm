<?php

declare(strict_types=1);

namespace App\RideHailing\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\RideHailing\Repository\PaymentRepository::class)]
#[ORM\Table(name: "ride_hailing_payments")]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public const METHOD_CASH = 'cash';
    public const METHOD_WALLET = 'wallet';
    public const METHOD_CARD = 'card';
    public const METHOD_BANK = 'bank';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\OneToOne(targetEntity: Ride::class, inversedBy: "payment")]
    private ?Ride $ride = null;

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

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $providerTransactionId = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $commissionAmount = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $driverEarning = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $metadata = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $paidAt = null;

    public function __construct()
    {
        $this->transactionId = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
        $this->createdAt = new \DateTime();
    }

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


    public function getRide()
    {
        return $this->ride;
    }

    public function setRide(?Ride $ride)
    {
        $this->ride = $ride;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentProvider()
    {
        return $this->paymentProvider;
    }

    public function setPaymentProvider(?string $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;
        return $this;
    }

    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    public function setProviderTransactionId(?string $providerTransactionId)
    {
        $this->providerTransactionId = $providerTransactionId;
        return $this;
    }

    public function getCommissionAmount()
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(?float $commissionAmount)
    {
        $this->commissionAmount = $commissionAmount;
        return $this;
    }

    public function getDriverEarning()
    {
        return $this->driverEarning;
    }

    public function setDriverEarning(?float $driverEarning)
    {
        $this->driverEarning = $driverEarning;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getPaidAt()
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTime $paidAt)
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
