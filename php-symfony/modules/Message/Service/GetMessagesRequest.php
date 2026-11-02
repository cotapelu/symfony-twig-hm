<?php

declare(strict_types=1);

namespace App\Message\Service;

class GetMessagesRequest
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $userId,
        public readonly int $limit = 50,
        public readonly int $offset = 0,
    ) {
    }
}
