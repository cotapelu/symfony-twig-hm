<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class WorkHistoryResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $officerId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly string $departmentCode,
        public readonly string $positionCode,
        public readonly ?string $notes,
        public readonly string $createdAt
    ) {
    }

    public static function fromEntity(\App\Government\Domain\Entity\WorkHistory $workHistory): self
    {
        return new self(
            id: $workHistory->getId(),
            officerId: $workHistory->getOfficerId()->getValue(),
            title: $workHistory->getTitle(),
            description: $workHistory->getDescription(),
            startDate: $workHistory->getStartDate()->format('Y-m-d'),
            endDate: $workHistory->getEndDate()?->format('Y-m-d'),
            departmentCode: $workHistory->getDepartmentCode(),
            positionCode: $workHistory->getPositionCode(),
            notes: $workHistory->getNotes(),
            createdAt: $workHistory->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }
}
