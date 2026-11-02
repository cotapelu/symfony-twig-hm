<?php

namespace App\Groups\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class GroupLeftEvent extends DomainEvent
{
    public function __construct(
        private readonly string $groupId,
        private readonly string $userId,
        private readonly string $memberId
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'GroupLeft';
    }

    protected function eventData(): array
    {
        return [
            'group_id' => $this->groupId,
            'user_id' => $this->userId,
            'member_id' => $this->memberId,
            'occurred_at' => (new \DateTime())->format('c'),
        ];
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getMemberId(): string
    {
        return $this->memberId;
    }
}