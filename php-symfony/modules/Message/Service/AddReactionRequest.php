<?php

declare(strict_types=1);

namespace App\Message\Service;

class AddReactionRequest
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $userId,
        public readonly string $emoji,
    ) {
    }
}
