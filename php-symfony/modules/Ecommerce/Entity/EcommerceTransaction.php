<?php

declare(strict_types=1);

namespace App\Ecommerce\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ecommerce_transactions')]
#[ORM\HasLifecycleCallbacks]
class EcommerceTransaction
{
    public const TYPE_PAYMENT = 'PAYMENT';
    public const TYPE_REFUND = 'REFUND';
    public const TYPE_RESERVATION = 'RESERVATION';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EcommerceOrder::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private EcommerceOrder $order;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(EcommerceOrder $order, string $type, int $amountCents)
    {
        $this->order = $order;
        $this->type = $type;
        $this->amountCents = $amountCents;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder(EcommerceOrder $order)
    {
        $this->order = $order;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getAmountCents()
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents)
    {
        $this->amountCents = $amountCents;
        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
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

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order->getId(),
            'type' => $this->type,
            'amountCents' => $this->amountCents,
            'paymentMethod' => $this->paymentMethod,
            'status' => $this->status,
            'transactionId' => $this->transactionId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
