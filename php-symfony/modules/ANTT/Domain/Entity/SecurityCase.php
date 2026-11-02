<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * ANTT Security Case Entity
 * Represents a formal security case/ investigation
 */
#[ORM\Entity]
#[ORM\Table(name: 'antt_security_cases')]
#[ORM\Index(name: 'idx_case_status', columns: ['status'])]
#[ORM\Index(name: 'idx_case_type', columns: ['case_type'])]
#[ORM\Index(name: 'idx_case_priority', columns: ['priority'])]
class SecurityCase
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $caseNumber;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'case_type', type: 'string', length: 50)]
    private string $caseType;

    #[ORM\Column(type: 'string', length: 50)]
    private string $priority;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(name: 'investigation_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $investigationOfficerId = null;

    #[ORM\Column(name: 'investigation_officer_name', type: 'string', length: 255, nullable: true)]
    private ?string $investigationOfficerName = null;

    #[ORM\Column(name: 'assigned_team', type: 'string', length: 100, nullable: true)]
    private ?string $assignedTeam = null;

    #[ORM\Column(name: 'incident_id', type: 'integer', nullable: true)]
    private ?int $incidentId = null;

    #[ORM\Column(name: 'related_cases', type: 'json', nullable: true)]
    private ?array $relatedCases = null;

    #[ORM\Column(name: 'suspects', type: 'json', nullable: true)]
    private ?array $suspects = null;

    #[ORM\Column(name: 'victims', type: 'json', nullable: true)]
    private ?array $victims = null;

    #[ORM\Column(name: 'evidence', type: 'json', nullable: true)]
    private ?array $evidence = null;

    #[ORM\Column(name: 'location_address', type: 'string', length: 500, nullable: true)]
    private ?string $locationAddress = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20, nullable: true)]
    private ?string $wardCode = null;

    #[ORM\Column(name: 'opened_at', type: 'datetime_immutable')]
    private DateTimeImmutable $openedAt;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[ORM\Column(name: 'resolution', type: 'text', nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(name: 'legal_action', type: 'string', length: 50, nullable: true)]
    private ?string $legalAction = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'confidential', type: 'boolean', options: ['default' => false])]
    private bool $confidential = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->openedAt = new DateTimeImmutable();
        $this->caseNumber = $this->generateCaseNumber();
        $this->status = 'open';
        $this->priority = 'medium';
    }

    private function generateCaseNumber(): string
    {
        return 'HS' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCaseNumber(): string
    {
        return $this->caseNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCaseType(): string
    {
        return $this->caseType;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getInvestigationOfficerId(): ?string
    {
        return $this->investigationOfficerId;
    }

    public function getInvestigationOfficerName(): ?string
    {
        return $this->investigationOfficerName;
    }

    public function getAssignedTeam(): ?string
    {
        return $this->assignedTeam;
    }

    public function getIncidentId(): ?int
    {
        return $this->incidentId;
    }

    public function getRelatedCases(): ?array
    {
        return $this->relatedCases;
    }

    public function getSuspects(): ?array
    {
        return $this->suspects;
    }

    public function getVictims(): ?array
    {
        return $this->victims;
    }

    public function getEvidence(): ?array
    {
        return $this->evidence;
    }

    public function getLocationAddress(): ?string
    {
        return $this->locationAddress;
    }

    public function getWardCode(): ?string
    {
        return $this->wardCode;
    }

    public function getOpenedAt(): DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function getLegalAction(): ?string
    {
        return $this->legalAction;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Setters
    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setCaseType(string $type): self
    {
        $this->caseType = $type;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();

        if ($status === 'closed') {
            $this->closedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function assignTo(?string $officerId, ?string $officerName, ?string $team = null): self
    {
        $this->investigationOfficerId = $officerId;
        $this->investigationOfficerName = $officerName;
        $this->assignedTeam = $team;
        $this->status = 'under_investigation';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function linkIncident(?int $incidentId): self
    {
        $this->incidentId = $incidentId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function addSuspect(array $suspect): self
    {
        $this->suspects[] = $suspect;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function addVictim(array $victim): self
    {
        $this->victims[] = $victim;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function addEvidence(array $evidence): self
    {
        $this->evidence[] = $evidence;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setLocation(?string $address, ?string $wardCode = null): self
    {
        $this->locationAddress = $address;
        $this->wardCode = $wardCode;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function resolve(string $resolution, ?string $legalAction = null): self
    {
        $this->resolution = $resolution;
        $this->legalAction = $legalAction;
        $this->status = 'resolved';
        $this->closedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function close(): self
    {
        $this->status = 'closed';
        $this->closedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function escalate(): self
    {
        $this->status = 'escalated';
        $this->priority = 'high';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'under_investigation', 'escalated'], true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'case_number' => $this->caseNumber,
            'title' => $this->title,
            'description' => $this->description,
            'case_type' => $this->caseType,
            'priority' => $this->priority,
            'status' => $this->status,
            'investigation_officer' => [
                'id' => $this->investigationOfficerId,
                'name' => $this->investigationOfficerName,
            ],
            'assigned_team' => $this->assignedTeam,
            'incident_id' => $this->incidentId,
            'suspects_count' => count($this->suspects ?? []),
            'victims_count' => count($this->victims ?? []),
            'evidence_count' => count($this->evidence ?? []),
            'location' => $this->locationAddress,
            'ward_code' => $this->wardCode,
            'opened_at' => $this->openedAt->format('Y-m-d H:i:s'),
            'closed_at' => $this->closedAt?->format('Y-m-d H:i:s'),
            'resolution' => $this->resolution,
            'legal_action' => $this->legalAction,
            'confidential' => $this->confidential,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
