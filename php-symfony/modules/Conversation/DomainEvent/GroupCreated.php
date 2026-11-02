<?php

declare(strict_types=1);

namespace App\Conversation\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class GroupCreated extends DomainEvent
{
    public function __construct(
        private string $groupId,
        private string $creatorId,
        private string $title,
        private ?string $description = null,
        private array $initialParticipants = [],
    ) {
        parent::__construct([
            'aggregateId' => $groupId,
            'aggregateType' => 'Group',
        ]);
    }

    public function eventType(): string
    {
        return 'group.created';
    }

    protected function eventData(): array
    {
        return [
            'groupId' => $this->groupId,
            'creatorId' => $this->creatorId,
            'title' => $this->title,
            'description' => $this->description,
            'initialParticipants' => $this->initialParticipants,
        ];
    }

    public function groupId(): string
    {
        return $this->groupId;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function title(): string
    {
        return $this->title;
    }
}