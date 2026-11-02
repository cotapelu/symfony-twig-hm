<?php

namespace App\Groups\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MemberRoleChangedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $groupId,
        private readonly string $userId,
        private readonly string $memberId,
        private readonly string $oldRole,
        private readonly string $newRole,
        private readonly string $actorId
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'MemberRoleChanged';
    }

    protected function eventData(): array
    {
        return [
            'group_id' => $this->groupId,
            'user_id' => $this->userId,
            'member_id' => $this->memberId,
            'old_role' => $this->oldRole,
            'new_role' => $this->newRole,
            'actor_id' => $this->actorId,
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

    public function getOldRole(): string
    {
        return $this->oldRole;
    }

    public function getNewRole(): string
    {
        return $this->newRole;
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }
}