<?php

declare(strict_types=1);

namespace App\Message\Event;

use App\Shared\DomainEvent\DomainEvent;

final class ReactionRemovedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $userId,
        public readonly string $emoji,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'reaction.removed';
    }

    protected function eventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'userId' => $this->userId,
            'emoji' => $this->emoji,
        ];
    }
}
