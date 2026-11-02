<?php

declare(strict_types=1);

namespace App\Subscription\Event;

use App\Shared\DomainEvent\DomainEvent;

class SubscriptionCreatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $subscriptionId,
        private readonly string $userId,
        private readonly string $planId,
        private readonly float $amount,
        private readonly \DateTime $createdAt,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'SubscriptionCreated';
    }

    protected function eventData(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'plan_id' => $this->planId,
            'amount' => $this->amount,
            'created_at' => $this->createdAt->format('c'),
        ];
    }

    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
