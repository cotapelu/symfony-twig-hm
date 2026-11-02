<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateProcedureRequest
{
    public function __construct(
        public string $name,
        public string $code,
        public string $category,
        public string $description = '',
        public array $requiredDocuments = [],
        public int $processingTimeDays = 1,
        public float $fee = 0.0
    ) {
    }
}
