<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class CreateHouseholdRequest
{
    public function __construct(
        public readonly string $householdNumber,
        public readonly string $province,
        public readonly string $district,
        public readonly string $ward,
        public readonly string $street,
        public readonly ?string $apartmentNumber,
        public readonly string $headOfHouseholdId
    ) {
    }
}
