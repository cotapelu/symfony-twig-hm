<?php

declare(strict_types=1);

namespace App\Subscription\Event;

use App\Shared\DomainEvent\DomainEvent;

class SubscriptionExpiredEvent extends DomainEvent
{
    public function __construct(
        private readonly string $subscriptionId,
        private readonly string $userId,
        private readonly string $planId,
        private readonly \DateTime $expiredAt,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'SubscriptionExpired';
    }

    protected function eventData(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'plan_id' => $this->planId,
            'expired_at' => $this->expiredAt->format('c'),
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

    public function getExpiredAt(): \DateTime
    {
        return $this->expiredAt;
    }
}
