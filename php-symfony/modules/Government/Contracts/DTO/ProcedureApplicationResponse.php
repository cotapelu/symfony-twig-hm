<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

use App\Government\Domain\Entity\ProcedureApplication;

class ProcedureApplicationResponse
{
    public function __construct(
        public string $id,
        public string $citizenId,
        public string $procedureId,
        public string $status,
        public array $documents,
        public ?string $notes,
        public string $submittedBy,
        public ?string $processedBy,
        public ?string $processingStartedAt,
        public ?string $completedAt,
        public ?string $result,
        public string $createdAt
    ) {
    }

    public static function fromEntity(ProcedureApplication $application): self
    {
        return new self(
            id: $application->getId(),
            citizenId: $application->getCitizenId(),
            procedureId: $application->getProcedureId(),
            status: $application->getStatus(),
            documents: $application->getDocuments(),
            notes: $application->getNotes(),
            submittedBy: $application->getSubmittedBy(),
            processedBy: $application->getProcessedBy(),
            processingStartedAt: $application->getProcessingStartedAt()?->format('Y-m-d H:i:s'),
            completedAt: $application->getCompletedAt()?->format('Y-m-d H:i:s'),
            result: $application->getResult(),
            createdAt: $application->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'citizenId' => $this->citizenId,
            'procedureId' => $this->procedureId,
            'status' => $this->status,
            'documents' => $this->documents,
            'notes' => $this->notes,
            'submittedBy' => $this->submittedBy,
            'processedBy' => $this->processedBy,
            'processingStartedAt' => $this->processingStartedAt,
            'completedAt' => $this->completedAt,
            'result' => $this->result,
            'createdAt' => $this->createdAt,
        ];
    }
}
