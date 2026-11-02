<?php

declare(strict_types=1);

namespace App\Message\Service;

class ProcessAnalyticsJob
{
    public function __construct(
        public readonly string $eventType,
        public readonly array $data,
    ) {
    }
}
