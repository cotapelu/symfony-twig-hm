<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gov_procedure_applications')]
class ProcedureApplication
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'citizen_id', type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(name: 'procedure_id', type: 'string', length: 36)]
    private string $procedureId;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status; // submitted, processing, pending_documents, approved, rejected, completed

    #[ORM\Column(type: 'json')]
    private array $documents;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(name: 'submitted_by', type: 'string', length: 36)]
    private string $submittedBy;

    #[ORM\Column(name: 'processed_by', type: 'string', length: 36, nullable: true)]
    private ?string $processedBy = null;

    #[ORM\Column(name: 'processing_started_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $processingStartedAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $result = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PENDING_DOCUMENTS = 'pending_documents';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        string $citizenId,
        string $procedureId,
        array $documents,
        string $submittedBy,
        ?string $notes = null
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->citizenId = $citizenId;
        $this->procedureId = $procedureId;
        $this->documents = $documents;
        $this->submittedBy = $submittedBy;
        $this->notes = $notes;
        $this->status = self::STATUS_SUBMITTED;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCitizenId(): string
    {
        return $this->citizenId;
    }

    public function getProcedureId(): string
    {
        return $this->procedureId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): void
    {
        $this->documents = $documents;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getSubmittedBy(): string
    {
        return $this->submittedBy;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?string $processedBy): void
    {
        $this->processedBy = $processedBy;
    }

    public function getProcessingStartedAt(): ?DateTimeImmutable
    {
        return $this->processingStartedAt;
    }

    public function startProcessing(string $processedBy): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->processedBy = $processedBy;
        $this->processingStartedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    public function approve(string $result): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->result = $result;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reject(string $result): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->result = $result;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function requestAdditionalDocuments(array $documents): void
    {
        $this->status = self::STATUS_PENDING_DOCUMENTS;
        $this->documents = array_merge($this->documents, $documents);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
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
            'processingStartedAt' => $this->processingStartedAt?->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'result' => $this->result,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
