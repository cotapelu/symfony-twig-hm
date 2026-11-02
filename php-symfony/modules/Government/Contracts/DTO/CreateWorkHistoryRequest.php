<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateWorkHistoryRequest
{
    public function __construct(
        public readonly string $officerId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $startDate,
        public readonly string $departmentCode,
        public readonly string $positionCode,
        public readonly ?string $endDate = null,
        public readonly ?string $notes = null
    ) {
    }
}
