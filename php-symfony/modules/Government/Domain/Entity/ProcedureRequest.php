<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gov_procedure_requests')]
#[ORM\Index(name: 'idx_proc_req_citizen', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_proc_req_status', columns: ['status'])]
#[ORM\Index(name: 'idx_proc_req_deadline', columns: ['deadline_date'])]
class ProcedureRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'citizen_id', type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(name: 'procedure_id', type: 'string', length: 36)]
    private string $procedureId;

    #[ORM\Column(name: 'assigned_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedOfficerId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'submitted_documents', type: 'json')]
    private array $submittedDocuments = [];

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(name: 'deadline_date', type: 'datetime_immutable')]
    private DateTimeImmutable $deadlineDate;

    #[ORM\Column(name: 'submitted_at', type: 'datetime_immutable')]
    private DateTimeImmutable $submittedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PENDING_DOCS = 'pending_documents';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const VALID_TRANSITIONS = [
        self::STATUS_SUBMITTED => [self::STATUS_PROCESSING, self::STATUS_PENDING_DOCS, self::STATUS_REJECTED],
        self::STATUS_PROCESSING => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_PENDING_DOCS],
        self::STATUS_PENDING_DOCS => [self::STATUS_PROCESSING, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_COMPLETED],
        self::STATUS_REJECTED => [],
        self::STATUS_COMPLETED => [],
    ];

    public function __construct(
        string $citizenId,
        string $procedureId,
        int $processingTimeDays
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->citizenId = $citizenId;
        $this->procedureId = $procedureId;
        $this->status = self::STATUS_SUBMITTED;
        $this->submittedAt = new DateTimeImmutable();
        $this->deadlineDate = $this->calculateDeadline($processingTimeDays);
        $this->createdAt = new DateTimeImmutable();
    }

    private function calculateDeadline(int $processingTimeDays): DateTimeImmutable
    {
        $deadline = new DateTimeImmutable();
        $daysAdded = 0;
        
        while ($daysAdded < $processingTimeDays) {
            $deadline = $deadline->modify('+1 day');
            $dayOfWeek = (int) $deadline->format('N');
            if ($dayOfWeek < 6) {
                $daysAdded++;
            }
        }
        
        return $deadline;
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

    public function getAssignedOfficerId(): ?string
    {
        return $this->assignedOfficerId;
    }

    public function assignOfficer(string $officerId): void
    {
        $this->assignedOfficerId = $officerId;
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

    public function getSubmittedDocuments(): array
    {
        return $this->submittedDocuments;
    }

    public function addDocument(string $documentType, string $documentUrl): void
    {
        $this->submittedDocuments[] = [
            'type' => $documentType,
            'url' => $documentUrl,
            'uploaded_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function transitionStatus(string $newStatus): void
    {
        if (!isset(self::VALID_TRANSITIONS[$this->status])) {
            throw new \InvalidArgumentException('Invalid current status');
        }

        if (!in_array($newStatus, self::VALID_TRANSITIONS[$this->status], true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid status transition from %s to %s', $this->status, $newStatus)
            );
        }

        $this->status = $newStatus;
        $this->updatedAt = new DateTimeImmutable();

        if ($newStatus === self::STATUS_COMPLETED) {
            $this->completedAt = new DateTimeImmutable();
        }
    }

    public function getDeadlineDate(): DateTimeImmutable
    {
        return $this->deadlineDate;
    }

    public function getSubmittedAt(): DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isOverdue(): bool
    {
        $now = new DateTimeImmutable();
        return $now > $this->deadlineDate && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_REJECTED], true);
    }

    public function getDaysRemaining(): int
    {
        $now = new DateTimeImmutable();
        if ($now > $this->deadlineDate) {
            return 0;
        }
        $interval = $now->diff($this->deadlineDate);
        return $interval->days;
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
            'assignedOfficerId' => $this->assignedOfficerId,
            'status' => $this->status,
            'deadlineDate' => $this->deadlineDate->format('Y-m-d'),
            'submittedAt' => $this->submittedAt->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'isOverdue' => $this->isOverdue(),
            'daysRemaining' => $this->getDaysRemaining(),
        ];
    }
}
