<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessageDeletedEvent extends DomainEvent
{
    public function __construct(
        private string $messageId,
        private string $deletedBy,
        private bool $forEveryone,
        private string $conversationId
    ) {
        parent::__construct([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'deleted_by' => $deletedBy,
            'for_everyone' => $forEveryone,
        ]);
    }

    public function eventType(): string
    {
        return 'MessageDeleted';
    }

    protected function eventData(): array
    {
        return [
            'message_id' => $this->messageId,
            'deleted_by' => $this->deletedBy,
            'for_everyone' => $this->forEveryone,
            'conversation_id' => $this->conversationId,
        ];
    }
}
