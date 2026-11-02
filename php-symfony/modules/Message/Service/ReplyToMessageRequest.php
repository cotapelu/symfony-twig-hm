<?php

declare(strict_types=1);

namespace App\Message\Service;

class ReplyToMessageRequest
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $conversationId,
        public readonly string $senderId,
        public readonly string $content,
        public readonly string $type = 'text',
        public readonly string $replyToMessageId,
        public readonly array $metadata = [],
    ) {
    }
}
