<?php

namespace App\Subscription\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Subscription\Repository\SubscriptionRepository::class)]
#[ORM\Table(name: 'subscriptions')]
#[ORM\Index(columns: ['user_id'], name: 'idx_subscription_user')]
#[ORM\Index(columns: ['plan_id'], name: 'idx_subscription_plan')]
#[ORM\Index(columns: ['status'], name: 'idx_subscription_status')]
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(name: 'plan_id', type: 'string', length: 100)]
    private string $planId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'started_at', type: 'datetime')]
    private \DateTime $startedAt;

    #[ORM\Column(name: 'ended_at', type: 'datetime', nullable: true)]
    private ?\DateTime $endedAt;

    #[ORM\Column(name: 'auto_renew', type: 'boolean')]
    private bool $autoRenew = true;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(type: 'string', length: 50)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        User $user,
        string $planId,
        float $amount,
        string $currency = 'USD',
        ?array $features = null,
        bool $autoRenew = true
    ) {
        $this->id = $this->generateId();
        $this->user = $user;
        $this->planId = $planId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->features = $features;
        $this->autoRenew = $autoRenew;
        $this->startedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    private function generateId()
    {
        return bin2hex(random_bytes(16));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPlanId()
    {
        return $this->planId;
    }

    public function setPlanId(string $planId)
    {
        $this->planId = $planId;
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

    public function getStartedAt()
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt()
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTime $endedAt)
    {
        $this->endedAt = $endedAt;
        return $this;
    }

    public function isAutoRenew()
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew)
    {
        $this->autoRenew = $autoRenew;
        return $this;
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

    public function getFeatures()
    {
        return $this->features;
    }

    public function setFeatures(?array $features)
    {
        $this->features = $features;
        return $this;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired()
    {
        if ($this->endedAt === null) {
            return false;
        }
        return $this->endedAt < new \DateTime();
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'planId' => $this->planId,
            'status' => $this->status,
            'startedAt' => $this->startedAt->format('Y-m-d H:i:s'),
            'endedAt' => $this->endedAt?->format('Y-m-d H:i:s'),
            'autoRenew' => $this->autoRenew,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'features' => $this->features,
            'isActive' => $this->isActive(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
