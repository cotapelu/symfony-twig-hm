<?php

namespace App\Groups\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class GroupCreatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $groupId,
        private readonly string $ownerId
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'GroupCreated';
    }

    protected function eventData(): array
    {
        return [
            'group_id' => $this->groupId,
            'owner_id' => $this->ownerId,
            'occurred_at' => (new \DateTime())->format('c'),
        ];
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }
}