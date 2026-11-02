<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

use App\Government\Domain\Entity\ProcedureRequest;

class ProcedureRequestResponse
{
    public function __construct(
        public string $id,
        public string $citizenId,
        public string $procedureId,
        public ?string $assignedOfficerId,
        public string $status,
        public string $deadlineDate,
        public string $submittedAt,
        public ?string $completedAt,
        public bool $isOverdue,
        public int $daysRemaining,
        public array $submittedDocuments = []
    ) {
    }

    public static function fromEntity(ProcedureRequest $request): self
    {
        return new self(
            id: $request->getId(),
            citizenId: $request->getCitizenId(),
            procedureId: $request->getProcedureId(),
            assignedOfficerId: $request->getAssignedOfficerId(),
            status: $request->getStatus(),
            deadlineDate: $request->getDeadlineDate()->format('Y-m-d'),
            submittedAt: $request->getSubmittedAt()->format('Y-m-d H:i:s'),
            completedAt: $request->getCompletedAt()?->format('Y-m-d H:i:s'),
            isOverdue: $request->isOverdue(),
            daysRemaining: $request->getDaysRemaining(),
            submittedDocuments: $request->getSubmittedDocuments()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'citizenId' => $this->citizenId,
            'procedureId' => $this->procedureId,
            'assignedOfficerId' => $this->assignedOfficerId,
            'status' => $this->status,
            'deadlineDate' => $this->deadlineDate,
            'submittedAt' => $this->submittedAt,
            'completedAt' => $this->completedAt,
            'isOverdue' => $this->isOverdue,
            'daysRemaining' => $this->daysRemaining,
            'submittedDocuments' => $this->submittedDocuments,
        ];
    }
}
