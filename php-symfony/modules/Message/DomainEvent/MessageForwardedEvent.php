<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessageForwardedEvent extends DomainEvent
{
    public function __construct(
        private string $forwardedMessageId,
        private string $originalMessageId,
        private string $originalConversationId,
        private string $targetConversationId,
        private string $forwardedBy
    ) {
        parent::__construct([
            'forwarded_message_id' => $forwardedMessageId,
            'original_message_id' => $originalMessageId,
            'original_conversation_id' => $originalConversationId,
            'target_conversation_id' => $targetConversationId,
            'forwarded_by' => $forwardedBy,
        ]);
    }

    public function eventType(): string
    {
        return 'MessageForwarded';
    }

    protected function eventData(): array
    {
        return [
            'forwarded_message_id' => $this->forwardedMessageId,
            'original_message_id' => $this->originalMessageId,
            'original_conversation_id' => $this->originalConversationId,
            'target_conversation_id' => $this->targetConversationId,
            'forwarded_by' => $this->forwardedBy,
        ];
    }
}
