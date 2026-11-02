<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateRewardRequest
{
    public function __construct(
        public readonly string $officerId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly string $date,
        public readonly string $decisionNumber,
        public readonly ?string $issuedBy = null,
        public readonly ?string $level = null,
        public readonly ?string $notes = null
    ) {
    }
}
