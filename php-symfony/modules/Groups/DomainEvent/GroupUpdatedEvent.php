<?php

namespace App\Groups\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class GroupUpdatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $groupId,
        private readonly array $changes
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'GroupUpdated';
    }

    protected function eventData(): array
    {
        return [
            'group_id' => $this->groupId,
            'changes' => $this->changes,
            'occurred_at' => (new \DateTime())->format('c'),
        ];
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }
}