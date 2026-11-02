<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.ecommerce_transactions')]
#[ORM\Index(name: 'idx_ecommerce_transactions_order_id', columns: ['order_id'])]
#[ORM\Index(name: 'idx_ecommerce_transactions_customer_id', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_ecommerce_transactions_status', columns: ['status'])]
#[ORM\Index(name: 'idx_ecommerce_transactions_created_at', columns: ['created_at'])]
class EcommerceTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $orderId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $paymentMethod;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $externalTransactionId = null;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $paymentDetails = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $orderId, string $customerId, string $paymentMethod, int $amountCents)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->paymentMethod = $paymentMethod;
        $this->amountCents = $amountCents;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId)
    {
        $this->customerId = $customerId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getExternalTransactionId()
    {
        return $this->externalTransactionId;
    }

    public function setExternalTransactionId(?string $externalTransactionId)
    {
        $this->externalTransactionId = $externalTransactionId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAmountCents()
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents)
    {
        $this->amountCents = $amountCents;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPaymentDetails()
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(?array $paymentDetails)
    {
        $this->paymentDetails = $paymentDetails;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    public function isProcessing()
    {
        return $this->status === 'PROCESSING';
    }

    public function isCompleted()
    {
        return $this->status === 'COMPLETED';
    }

    public function isFailed()
    {
        return $this->status === 'FAILED';
    }

    public function isRefunded()
    {
        return $this->status === 'REFUNDED';
    }

    public function isCreditCard()
    {
        return $this->paymentMethod === 'CREDIT_CARD';
    }

    public function isDebitCard()
    {
        return $this->paymentMethod === 'DEBIT_CARD';
    }

    public function isPayPal()
    {
        return $this->paymentMethod === 'PAYPAL';
    }

    public function isBankTransfer()
    {
        return $this->paymentMethod === 'BANK_TRANSFER';
    }

    public function hasExternalTransactionId()
    {
        return $this->externalTransactionId !== null;
    }

    public function hasPaymentDetails()
    {
        return $this->paymentDetails !== null;
    }
}
