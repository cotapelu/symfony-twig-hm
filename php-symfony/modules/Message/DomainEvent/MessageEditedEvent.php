<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessageEditedEvent extends DomainEvent
{
    public function __construct(
        private string $messageId,
        private string $oldContent,
        private string $newContent,
        private string $editedBy,
        private string $conversationId
    ) {
        parent::__construct([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'edited_by' => $editedBy,
        ]);
    }

    public function eventType(): string
    {
        return 'MessageEdited';
    }

    protected function eventData(): array
    {
        return [
            'message_id' => $this->messageId,
            'old_content' => $this->oldContent,
            'new_content' => $this->newContent,
            'edited_by' => $this->editedBy,
            'conversation_id' => $this->conversationId,
        ];
    }
}
