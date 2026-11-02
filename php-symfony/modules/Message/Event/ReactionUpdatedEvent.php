<?php

declare(strict_types=1);

namespace App\Message\Event;

use App\Shared\DomainEvent\DomainEvent;

final class ReactionUpdatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $userId,
        public readonly string $oldEmoji,
        public readonly string $newEmoji,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'reaction.updated';
    }

    protected function eventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'userId' => $this->userId,
            'oldEmoji' => $this->oldEmoji,
            'newEmoji' => $this->newEmoji,
        ];
    }
}
