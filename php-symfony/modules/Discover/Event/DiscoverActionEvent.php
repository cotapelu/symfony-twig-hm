<?php

declare(strict_types=1);

namespace App\Discover\Event;

use App\Shared\DomainEvent\DomainEvent;
use DateTimeImmutable;

class DiscoverActionEvent extends DomainEvent
{
    public function __construct(
        public readonly ?object $user,
        public readonly string $userId,
        public readonly string $actionType,
        public readonly string $contentType,
        public readonly string $contentId,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'discover.action';
    }

    protected function eventData(): array
    {
        return [
            'userId' => $this->userId,
            'actionType' => $this->actionType,
            'contentType' => $this->contentType,
            'contentId' => $this->contentId,
        ];
    }
}
