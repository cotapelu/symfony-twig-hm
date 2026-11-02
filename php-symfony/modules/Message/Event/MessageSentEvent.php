<?php

declare(strict_types=1);

namespace App\Message\Event;

use App\Shared\DomainEvent\DomainEvent;

class MessageSentEvent extends DomainEvent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $conversationId,
        public readonly string $senderId,
        public readonly string $content,
        public readonly string $type,
        public readonly ?\DateTime $createdAt,
        public readonly ?string $threadId = null,
        public readonly ?array $attachments = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'MessageSent';
    }

    protected function eventData(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'content' => $this->content,
            'type' => $this->type,
            'created_at' => $this->createdAt?->format('c'),
            'thread_id' => $this->threadId,
            'attachments' => $this->attachments,
        ];
    }
}
