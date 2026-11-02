<?php

declare(strict_types=1);

namespace App\Rewards\Event;

use App\Shared\DomainEvent\DomainEvent;

class BadgeEarnedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $badgeId,
        private readonly string $userId,
        private readonly string $badgeType,
        private readonly string $badgeName,
        private readonly \DateTime $earnedAt,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'BadgeEarned';
    }

    protected function eventData(): array
    {
        return [
            'badge_id' => $this->badgeId,
            'user_id' => $this->userId,
            'badge_type' => $this->badgeType,
            'badge_name' => $this->badgeName,
            'earned_at' => $this->earnedAt->format('c'),
        ];
    }

    public function getBadgeId(): string
    {
        return $this->badgeId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getBadgeType(): string
    {
        return $this->badgeType;
    }

    public function getBadgeName(): string
    {
        return $this->badgeName;
    }

    public function getEarnedAt(): \DateTime
    {
        return $this->earnedAt;
    }
}
