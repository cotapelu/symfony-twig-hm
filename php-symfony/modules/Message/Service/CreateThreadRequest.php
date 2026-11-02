<?php

declare(strict_types=1);

namespace App\Message\Service;

class CreateThreadRequest
{
    public function __construct(
        public readonly string $rootMessageId,
        public readonly ?string $threadId = null,
    ) {
    }
}
