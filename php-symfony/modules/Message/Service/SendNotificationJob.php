<?php

declare(strict_types=1);

namespace App\Message\Service;

class SendNotificationJob
{
    public function __construct(
        public readonly string $userId,
        public readonly string $type,
        public readonly array $data,
    ) {
    }
}
