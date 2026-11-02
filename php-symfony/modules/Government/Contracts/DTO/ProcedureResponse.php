<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

use App\Government\Domain\Entity\AdministrativeProcedure;

class ProcedureResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public string $category,
        public string $description,
        public array $requiredDocuments,
        public int $processingTimeDays,
        public float $fee,
        public string $status,
        public string $createdAt
    ) {
    }

    public static function fromEntity(AdministrativeProcedure $procedure): self
    {
        return new self(
            id: $procedure->getId(),
            name: $procedure->getName(),
            code: $procedure->getCode(),
            category: $procedure->getCategory(),
            description: $procedure->getDescription(),
            requiredDocuments: $procedure->getRequiredDocuments(),
            processingTimeDays: $procedure->getProcessingTimeDays(),
            fee: $procedure->getFee(),
            status: $procedure->getStatus(),
            createdAt: $procedure->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'requiredDocuments' => $this->requiredDocuments,
            'processingTimeDays' => $this->processingTimeDays,
            'fee' => $this->fee,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
