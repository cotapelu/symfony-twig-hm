<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class RewardResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $officerId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly string $date,
        public readonly string $decisionNumber,
        public readonly ?string $issuedBy,
        public readonly ?string $level,
        public readonly ?string $notes,
        public readonly string $createdAt
    ) {
    }

    public static function fromEntity(\App\Government\Domain\Entity\Reward $reward): self
    {
        return new self(
            id: $reward->getId(),
            officerId: $reward->getOfficerId()->getValue(),
            type: $reward->getType(),
            title: $reward->getTitle(),
            description: $reward->getDescription(),
            date: $reward->getDate()->format('Y-m-d'),
            decisionNumber: $reward->getDecisionNumber(),
            issuedBy: $reward->getIssuedBy(),
            level: $reward->getLevel(),
            notes: $reward->getNotes(),
            createdAt: $reward->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }
}
