<?php

declare(strict_types=1);

namespace App\Realtime\Service;

class TypingIndicatorRequest
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $userId,
        public readonly bool $isTyping,
    ) {
    }
}
