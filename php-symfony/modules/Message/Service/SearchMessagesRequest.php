<?php

declare(strict_types=1);

namespace App\Message\Service;

class SearchMessagesRequest
{
    public function __construct(
        public readonly string $query,
        public readonly ?string $conversationId = null,
        public readonly ?string $userId = null,
        public readonly ?int $limit = 50,
        public readonly ?int $offset = 0,
    ) {
    }
}
