<?php

declare(strict_types=1);

namespace App\Message\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class MessageSent extends DomainEvent
{
    public function __construct(
        private string $messageId,
        private string $conversationId,
        private string $senderId,
        private string $content,
        private string $messageType = 'text',
        private ?array $attachments = null,
    ) {
        parent::__construct([
            'aggregateId' => $messageId,
            'aggregateType' => 'Message',
            'conversationId' => $conversationId,
        ]);
    }

    public function eventType(): string
    {
        return 'message.sent';
    }

    protected function eventData(): array
    {
        return [
            'messageId' => $this->messageId,
            'conversationId' => $this->conversationId,
            'senderId' => $this->senderId,
            'content' => $this->content,
            'messageType' => $this->messageType,
            'attachments' => $this->attachments,
        ];
    }

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function conversationId(): string
    {
        return $this->conversationId;
    }

    public function senderId(): string
    {
        return $this->senderId;
    }

    public function content(): string
    {
        return $this->content;
    }
}