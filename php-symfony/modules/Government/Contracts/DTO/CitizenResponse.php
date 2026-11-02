<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

use App\Government\Domain\Entity\Citizen;

class CitizenResponse
{
    public function __construct(
        public string $id,
        public string $fullName,
        public ?string $email,
        public ?string $phone,
        public ?string $dateOfBirth,
        public ?string $identityNumber,
        public string $gender,
        public array $registeredAddress,
        public bool $isActive,
        public ?string $occupation,
        public ?string $maritalStatus,
        public ?string $householdId,
        public ?int $age,
        public string $createdAt
    ) {
    }

    public static function fromEntity(Citizen $citizen): self
    {
        return new self(
            id: $citizen->getId()->getValue(),
            fullName: $citizen->getFullName(),
            email: $citizen->getEmail(),
            phone: $citizen->getPhone(),
            dateOfBirth: $citizen->getDateOfBirth()?->format('Y-m-d'),
            identityNumber: $citizen->getIdentityNumber(),
            gender: $citizen->getGender(),
            registeredAddress: $citizen->getRegisteredAddress()->toArray(),
            isActive: $citizen->isActive(),
            occupation: $citizen->getOccupation(),
            maritalStatus: $citizen->getMaritalStatus(),
            householdId: $citizen->getHouseholdId(),
            age: $citizen->getAge(),
            createdAt: $citizen->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'dateOfBirth' => $this->dateOfBirth,
            'identityNumber' => $this->identityNumber,
            'gender' => $this->gender,
            'registeredAddress' => $this->registeredAddress,
            'isActive' => $this->isActive,
            'occupation' => $this->occupation,
            'maritalStatus' => $this->maritalStatus,
            'householdId' => $this->householdId,
            'age' => $this->age,
            'createdAt' => $this->createdAt,
        ];
    }
}
