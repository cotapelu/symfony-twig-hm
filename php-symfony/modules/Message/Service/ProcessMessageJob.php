<?php

declare(strict_types=1);

namespace App\Message\Service;

class ProcessMessageJob
{
    public function __construct(
        public readonly string $messageId,
        public readonly array $data,
    ) {
    }
}
