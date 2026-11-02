<?php

declare(strict_types=1);

namespace App\Message;

class NotificationMessage
{
    public function __construct(
        public readonly string $userId,
        public readonly string $message,
        public readonly string $type = 'info',
    ) {
    }
}
