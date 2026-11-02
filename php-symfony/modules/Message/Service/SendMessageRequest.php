<?php

declare(strict_types=1);

namespace App\Message\Service;

class SendMessageRequest
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $content,
        public readonly ?string $replyToMessageId = null,
        public readonly ?string $threadId = null,
        public readonly ?array $attachments = null,
        public readonly ?string $userId = null,
    ) {
    }

    // Method to get the authenticated user ID
    public function getUserId()
    {
        return $this->userId;
    }
}
