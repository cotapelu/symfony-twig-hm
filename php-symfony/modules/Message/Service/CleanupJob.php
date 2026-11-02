<?php

declare(strict_types=1);

namespace App\Message\Service;

class CleanupJob
{
    public function __construct(
        public readonly string $type,
        public readonly array $criteria,
    ) {
    }
}
