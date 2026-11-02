<?php

declare(strict_types=1);

namespace App\Message\Service;

class GetThreadRequest
{
    public function __construct(
        public readonly int $threadId,
    ) {
    }
}
