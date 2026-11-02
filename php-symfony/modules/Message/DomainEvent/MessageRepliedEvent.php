<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessageRepliedEvent extends DomainEvent
{
    public function __construct(
        private string $replyId,
        private string $replyToMessageId,
        private string $threadId,
        private string $conversationId,
        private string $repliedBy
    ) {
        parent::__construct([
            'reply_id' => $replyId,
            'reply_to_message_id' => $replyToMessageId,
            'thread_id' => $threadId,
            'conversation_id' => $conversationId,
            'replied_by' => $repliedBy,
        ]);
    }

    public function eventType(): string
    {
        return 'MessageReplied';
    }

    protected function eventData(): array
    {
        return [
            'reply_id' => $this->replyId,
            'reply_to_message_id' => $this->replyToMessageId,
            'thread_id' => $this->threadId,
            'conversation_id' => $this->conversationId,
            'replied_by' => $this->repliedBy,
        ];
    }
}
