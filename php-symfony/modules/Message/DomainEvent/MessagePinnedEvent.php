<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessagePinnedEvent extends DomainEvent
{
    public function __construct(
        private string $messageId,
        private string $conversationId,
        private string $userId,
        private bool $isPinned
    ) {
        parent::__construct([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'is_pinned' => $isPinned,
        ]);
    }

    public function eventType(): string
    {
        return 'MessagePinned';
    }

    protected function eventData(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'is_pinned' => $this->isPinned,
        ];
    }
}
