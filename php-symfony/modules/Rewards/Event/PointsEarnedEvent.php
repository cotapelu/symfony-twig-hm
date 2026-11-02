<?php

declare(strict_types=1);

namespace App\Rewards\Event;

use App\Shared\DomainEvent\DomainEvent;

class PointsEarnedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly int $points,
        private readonly string $reason,
        private readonly int $newTotal,
        private readonly \DateTime $earnedAt,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'PointsEarned';
    }

    protected function eventData(): array
    {
        return [
            'user_id' => $this->userId,
            'points' => $this->points,
            'reason' => $this->reason,
            'new_total' => $this->newTotal,
            'earned_at' => $this->earnedAt->format('c'),
        ];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getNewTotal(): int
    {
        return $this->newTotal;
    }

    public function getEarnedAt(): \DateTime
    {
        return $this->earnedAt;
    }
}
