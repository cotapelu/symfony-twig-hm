<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class OfficerResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $dateOfBirth,
        public readonly ?string $identityNumber,
        public readonly array $position,
        public readonly array $department,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly bool $isActive
    ) {
    }

    public static function fromEntity(\App\Government\Domain\Entity\Officer $officer): self
    {
        return new self(
            id: $officer->getId()->getValue(),
            fullName: $officer->getFullName(),
            email: $officer->getEmail() ?? '',
            phone: $officer->getPhone() ?? '',
            dateOfBirth: $officer->getDateOfBirth()?->format('Y-m-d'),
            identityNumber: $officer->getIdentityNumber(),
            position: [
                'code' => $officer->getPosition()->getCode(),
                'name' => $officer->getPosition()->getName(),
                'level' => $officer->getPosition()->getLevel()
            ],
            department: [
                'code' => $officer->getDepartment()->getCode(),
                'name' => $officer->getDepartment()->getName(),
                'parentCode' => $officer->getDepartment()->getParentCode()
            ],
            createdAt: $officer->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $officer->getUpdatedAt()?->format('Y-m-d H:i:s'),
            isActive: $officer->isActive()
        );
    }
}
