<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateProcedureApplicationRequest
{
    public function __construct(
        public string $citizenId,
        public string $procedureId,
        public array $documents = [],
        public string $submittedBy,
        public ?string $notes = null
    ) {
    }
}
