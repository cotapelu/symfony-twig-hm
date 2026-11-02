<?php

declare(strict_types=1);

namespace App\User\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class UserPresenceChanged extends DomainEvent
{
    public function __construct(
        private string $userId,
        private string $status, // 'online', 'offline', 'away'
        private ?string $lastSeen = null,
    ) {
        parent::__construct([
            'aggregateId' => $userId,
            'aggregateType' => 'User',
        ]);
    }

    public function eventType(): string
    {
        return 'user.presence.changed';
    }

    protected function eventData(): array
    {
        return [
            'userId' => $this->userId,
            'status' => $this->status,
            'lastSeen' => $this->lastSeen,
        ];
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function status(): string
    {
        return $this->status;
    }
}