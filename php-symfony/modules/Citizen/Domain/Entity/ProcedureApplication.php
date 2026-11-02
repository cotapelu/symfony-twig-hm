<?php

declare(strict_types=1);

namespace App\Citizen\Domain\Entity;

use App\AppCore\Event\EventInterface;
use App\AppCore\Event\Trait\TimestampableTrait;
use App\AppCore\Transaction\Trait\SoftDeletableTrait;
use App\Government\Domain\Entity\AdministrativeProcedure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'procedure_applications')]
#[ORM\HasLifecycleCallbacks]
class ProcedureApplication implements EventInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', nullable: false)]
    private Citizen $citizen;

    #[ORM\ManyToOne(targetEntity: AdministrativeProcedure::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'procedure_id', referencedColumnName: 'id', nullable: false)]
    private AdministrativeProcedure $procedure;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'json')]
    private array $applicationData = [];

    #[ORM\Column(type: 'json')]
    private array $attachedDocuments = [];

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $submissionDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $expectedCompletionDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $actualCompletionDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $processedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(targetEntity: ApplicationStatusHistory::class, mappedBy: 'application')]
    private Collection $statusHistory;

    public function __construct(
        Citizen $citizen,
        AdministrativeProcedure $procedure
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->citizen = $citizen;
        $this->procedure = $procedure;
        $this->statusHistory = new ArrayCollection();
    }

    public function getEventName(): string
    {
        return 'procedure_application.updated';
    }

    public function getEventData(): array
    {
        return [
            'applicationId' => $this->id,
            'citizenId' => $this->citizen->getId(),
            'procedureId' => $this->procedure->getId(),
            'status' => $this->status,
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->updatedAt ?? $this->createdAt ?? new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCitizen(): Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(Citizen $citizen): self
    {
        $this->citizen = $citizen;
        return $this;
    }

    public function getProcedure(): AdministrativeProcedure
    {
        return $this->procedure;
    }

    public function setProcedure(AdministrativeProcedure $procedure): self
    {
        $this->procedure = $procedure;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getApplicationData(): array
    {
        return $this->applicationData;
    }

    public function setApplicationData(array $applicationData): self
    {
        $this->applicationData = $applicationData;
        return $this;
    }

    public function getAttachedDocuments(): array
    {
        return $this->attachedDocuments;
    }

    public function setAttachedDocuments(array $attachedDocuments): self
    {
        $this->attachedDocuments = $attachedDocuments;
        return $this;
    }

    public function getSubmissionDate(): ?\DateTimeInterface
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?\DateTimeInterface $submissionDate): self
    {
        $this->submissionDate = $submissionDate;
        return $this;
    }

    public function getExpectedCompletionDate(): ?\DateTimeInterface
    {
        return $this->expectedCompletionDate;
    }

    public function setExpectedCompletionDate(?\DateTimeInterface $expectedCompletionDate): self
    {
        $this->expectedCompletionDate = $expectedCompletionDate;
        return $this;
    }

    public function getActualCompletionDate(): ?\DateTimeInterface
    {
        return $this->actualCompletionDate;
    }

    public function setActualCompletionDate(?\DateTimeInterface $actualCompletionDate): self
    {
        $this->actualCompletionDate = $actualCompletionDate;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?string $processedBy): self
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Collection<int, ApplicationStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(ApplicationStatusHistory $statusHistory): self
    {
        if (!$this->statusHistory->contains($statusHistory)) {
            $this->statusHistory->add($statusHistory);
            $statusHistory->setApplication($this);
        }
        return $this;
    }

    public function removeStatusHistory(ApplicationStatusHistory $statusHistory): self
    {
        if ($this->statusHistory->removeElement($statusHistory)) {
            $statusHistory->setApplication($this);
        }
        return $this;
    }

    public function submit(): self
    {
        $this->status = self::STATUS_SUBMITTED;
        $this->submissionDate = new \DateTime();
        $this->expectedCompletionDate = (new \DateTime())->add(
            new \DateInterval(sprintf('P%dD', $this->procedure->getProcessingTimeDays()))
        );
        return $this;
    }

    public function approve(?string $processedBy = null): self
    {
        $this->status = self::STATUS_APPROVED;
        $this->actualCompletionDate = new \DateTime();
        $this->processedBy = $processedBy;
        return $this;
    }

    public function reject(string $rejectionReason, ?string $processedBy = null): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->actualCompletionDate = new \DateTime();
        $this->rejectionReason = $rejectionReason;
        $this->processedBy = $processedBy;
        return $this;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true);
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_COMPLETED], true);
    }

    public function getProcessingDaysCount(): ?int
    {
        if (!$this->submissionDate || !$this->actualCompletionDate) {
            return null;
        }

        return $this->submissionDate->diff($this->actualCompletionDate)->days;
    }

    public function isOverdue(): bool
    {
        if (!$this->expectedCompletionDate || $this->isCompleted()) {
            return false;
        }

        return new \DateTime() > $this->expectedCompletionDate;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'citizen' => $this->citizen->toArray(),
            'procedure' => $this->procedure->toArray(),
            'status' => $this->status,
            'applicationData' => $this->applicationData,
            'attachedDocuments' => $this->attachedDocuments,
            'submissionDate' => $this->submissionDate?->format('Y-m-d'),
            'expectedCompletionDate' => $this->expectedCompletionDate?->format('Y-m-d'),
            'actualCompletionDate' => $this->actualCompletionDate?->format('Y-m-d'),
            'rejectionReason' => $this->rejectionReason,
            'processedBy' => $this->processedBy,
            'notes' => $this->notes,
            'isPending' => $this->isPending(),
            'isProcessing' => $this->isProcessing(),
            'isCompleted' => $this->isCompleted(),
            'isOverdue' => $this->isOverdue(),
            'processingDaysCount' => $this->getProcessingDaysCount(),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}