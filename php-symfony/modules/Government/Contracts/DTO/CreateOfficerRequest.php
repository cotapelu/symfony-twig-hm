<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateOfficerRequest
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $positionCode,
        public readonly string $positionName,
        public readonly string $departmentCode,
        public readonly string $departmentName,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $identityNumber = null,
        public readonly int $positionLevel = 0,
        public readonly ?string $parentDepartmentCode = null
    ) {
    }
}
