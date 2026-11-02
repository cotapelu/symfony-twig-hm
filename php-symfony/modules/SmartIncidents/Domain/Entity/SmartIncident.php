<?php

declare(strict_types=1);

namespace App\SmartIncidents\Domain\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * SmartIncident Entity (Enhanced from ANTT Incident)
 * Represents a smart incident with metadata, attachments, messages, and AI features
 */
#[ORM\Entity]
#[ORM\Table(name: 'smart_incidents')]
#[ORM\Index(name: 'idx_smart_incident_status', columns: ['status'])]
#[ORM\Index(name: 'idx_smart_incident_reported_at', columns: ['reported_at'])]
#[ORM\Index(name: 'idx_smart_incident_ward_code', columns: ['ward_code'])]
#[ORM\Index(name: 'idx_smart_incident_severity', columns: ['severity'])]
class SmartIncident
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $incidentNumber;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $incidentType;

    #[ORM\Column(type: 'string', length: 50)]
    private string $severity;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    // Reporter Info
    #[ORM\Column(name: 'reporter_name', type: 'string', length: 255, nullable: true)]
    private ?string $reporterName = null;

    #[ORM\Column(name: 'reporter_phone', type: 'string', length: 20, nullable: true)]
    private ?string $reporterPhone = null;

    #[ORM\Column(name: 'reporter_anonymous', type: 'boolean', options: ['default' => false])]
    private bool $reporterAnonymous = false;

    // Location
    #[ORM\Column(name: 'location_address', type: 'string', length: 500, nullable: true)]
    private ?string $locationAddress = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20, nullable: true)]
    private ?string $wardCode = null;

    // Assignment
    #[ORM\Column(name: 'assigned_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedOfficerId = null;

    #[ORM\Column(name: 'assigned_team', type: 'string', length: 100, nullable: true)]
    private ?string $assignedTeam = null;

    // Timeline
    #[ORM\Column(name: 'reported_at', type: 'datetime_immutable')]
    private DateTimeImmutable $reportedAt;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    // Resolution
    #[ORM\Column(name: 'resolution_summary', type: 'text', nullable: true)]
    private ?string $resolutionSummary = null;

    // Metadata (JSON stored as TEXT)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metadataJson = null;

    // Statistics
    #[ORM\Column(name: 'view_count', type: 'integer', options: ['default' => 0])]
    private int $viewCount = 0;

    #[ORM\Column(name: 'upvote_count', type: 'integer', options: ['default' => 0])]
    private int $upvoteCount = 0;

    // Relations
    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: SmartIncidentAttachment::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: SmartIncidentMessage::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: SmartIncidentActivity::class, cascade: ['persist', 'remove'])]
    private Collection $activityLog;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->reportedAt = new DateTimeImmutable();
        $this->incidentNumber = $this->generateIncidentNumber();
        $this->status = 'reported';
        $this->severity = 'medium';
        $this->attachments = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->activityLog = new ArrayCollection();
    }

    private function generateIncidentNumber(): string
    {
        return 'BAO' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncidentNumber(): string
    {
        return $this->incidentNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getIncidentType(): string
    {
        return $this->incidentType;
    }

    public function setIncidentType(string $incidentType): self
    {
        $this->incidentType = $incidentType;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();

        if ($status === 'resolved') {
            $this->resolvedAt = new DateTimeImmutable();
        } elseif ($status === 'closed') {
            $this->closedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getReporterName(): ?string
    {
        return $this->reporterName;
    }

    public function setReporterName(?string $name): self
    {
        $this->reporterName = $name;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getReporterPhone(): ?string
    {
        return $this->reporterPhone;
    }

    public function setReporterPhone(?string $phone): self
    {
        $this->reporterPhone = $phone;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function isReporterAnonymous(): bool
    {
        return $this->reporterAnonymous;
    }

    public function setReporterAnonymous(bool $anonymous): self
    {
        $this->reporterAnonymous = $anonymous;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getLocationAddress(): ?string
    {
        return $this->locationAddress;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLocation(?string $address, ?float $lat = null, ?float $lon = null): self
    {
        $this->locationAddress = $address;
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getWardCode(): ?string
    {
        return $this->wardCode;
    }

    public function setWardCode(?string $wardCode): self
    {
        $this->wardCode = $wardCode;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getAssignedOfficerId(): ?string
    {
        return $this->assignedOfficerId;
    }

    public function setAssignedOfficerId(?string $officerId): self
    {
        $this->assignedOfficerId = $officerId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getAssignedTeam(): ?string
    {
        return $this->assignedTeam;
    }

    public function setAssignedTeam(?string $team): self
    {
        $this->assignedTeam = $team;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getReportedAt(): DateTimeImmutable
    {
        return $this->reportedAt;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getResolutionSummary(): ?string
    {
        return $this->resolutionSummary;
    }

    public function resolve(string $summary): self
    {
        $this->resolutionSummary = $summary;
        $this->resolvedAt = new DateTimeImmutable();
        $this->status = 'resolved';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function close(): self
    {
        $this->closedAt = new DateTimeImmutable();
        $this->status = 'closed';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function assignTo(?string $officerId, ?string $team = null): self
    {
        $this->assignedOfficerId = $officerId;
        $this->assignedTeam = $team;
        $this->status = 'assigned';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function escalate(): self
    {
        $this->status = 'escalated';
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['reported', 'assigned', 'in_progress', 'awaiting_info'], true);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Attachments
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(SmartIncidentAttachment $attachment): self
    {
        $attachment->setIncident($this);
        $this->attachments->add($attachment);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function removeAttachment(SmartIncidentAttachment $attachment): self
    {
        $this->attachments->removeElement($attachment);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    // Messages
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(SmartIncidentMessage $message): self
    {
        $message->setIncident($this);
        $this->messages->add($message);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    // Activity Log
    public function getActivityLog(): Collection
    {
        return $this->activityLog;
    }

    public function addActivity(SmartIncidentActivity $activity): self
    {
        $activity->setIncident($this);
        $this->activityLog->add($activity);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    // Statistics
    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function incrementViewCount(): self
    {
        $this->viewCount++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getUpvoteCount(): int
    {
        return $this->upvoteCount;
    }

    public function incrementUpvote(): self
    {
        $this->upvoteCount++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    // Metadata
    public function getMetadata(): array
    {
        if ($this->metadataJson === null) {
            return [];
        }
        return json_decode($this->metadataJson, true) ?? [];
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadataJson = json_encode($metadata);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getReportSource(): string
    {
        return $this->getMetadata()['reportSource'] ?? 'web';
    }

    public function getKeywords(): array
    {
        return $this->getMetadata()['keywords'] ?? [];
    }

    public function getTags(): array
    {
        return $this->getMetadata()['tags'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'incident_number' => $this->incidentNumber,
            'title' => $this->title,
            'description' => $this->description,
            'incident_type' => $this->incidentType,
            'severity' => $this->severity,
            'status' => $this->status,
            'reporter_name' => $this->reporterName,
            'reporter_phone' => $this->reporterPhone,
            'reporter_anonymous' => $this->reporterAnonymous,
            'location_address' => $this->locationAddress,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ward_code' => $this->wardCode,
            'assigned_officer_id' => $this->assignedOfficerId,
            'assigned_team' => $this->assignedTeam,
            'reported_at' => $this->reportedAt->format('Y-m-d H:i:s'),
            'resolved_at' => $this->resolvedAt?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closedAt?->format('Y-m-d H:i:s'),
            'resolution_summary' => $this->resolutionSummary,
            'metadata' => $this->getMetadata(),
            'attachments_count' => $this->attachments->count(),
            'messages_count' => $this->messages->count(),
            'view_count' => $this->viewCount,
            'upvote_count' => $this->upvoteCount,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
