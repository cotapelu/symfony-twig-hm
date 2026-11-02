<?php

namespace App\Groups\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class GroupDeletedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $groupId
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'GroupDeleted';
    }

    protected function eventData(): array
    {
        return [
            'group_id' => $this->groupId,
            'occurred_at' => (new \DateTime())->format('c'),
        ];
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }
}