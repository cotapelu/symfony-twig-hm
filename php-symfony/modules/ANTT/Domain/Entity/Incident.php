<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * ANTT Incident Entity
 * Represents a security incident reported in the ward
 */
#[ORM\Entity]
#[ORM\Table(name: 'antt_incidents')]
#[ORM\Index(name: 'idx_incident_status', columns: ['status'])]
#[ORM\Index(name: 'idx_incident_reported_at', columns: ['reported_at'])]
#[ORM\Index(name: 'idx_incident_ward_code', columns: ['ward_code'])]
class Incident
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

    #[ORM\Column(name: 'reporter_name', type: 'string', length: 255, nullable: true)]
    private ?string $reporterName = null;

    #[ORM\Column(name: 'reporter_phone', type: 'string', length: 20, nullable: true)]
    private ?string $reporterPhone = null;

    #[ORM\Column(name: 'reporter_anonymous', type: 'boolean', options: ['default' => false])]
    private bool $reporterAnonymous = false;

    #[ORM\Column(name: 'location_address', type: 'string', length: 500, nullable: true)]
    private ?string $locationAddress = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20, nullable: false)]
    private ?string $wardCode = null;

    #[ORM\Column(name: 'reporter_id', type: 'string', length: 100, nullable: true)]
    private ?string $reporterId = null;

    #[ORM\Column(name: 'assigned_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedOfficerId = null;

    #[ORM\Column(name: 'assigned_team', type: 'string', length: 100, nullable: true)]
    private ?string $assignedTeam = null;

    #[ORM\Column(name: 'reported_at', type: 'datetime_immutable')]
    private DateTimeImmutable $reportedAt;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[ORM\Column(name: 'resolution_summary', type: 'text', nullable: true)]
    private ?string $resolutionSummary = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIncidentType(): string
    {
        return $this->incidentType;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReporterName(): ?string
    {
        return $this->reporterName;
    }

    public function getReporterPhone(): ?string
    {
        return $this->reporterPhone;
    }

    public function isReporterAnonymous(): bool
    {
        return $this->reporterAnonymous;
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

    public function getWardCode(): ?string
    {
        return $this->wardCode;
    }

    public function getReporterId(): ?string
    {
        return $this->reporterId;
    }

    public function setReporterId(?string $reporterId): self
    {
        $this->reporterId = $reporterId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getAssignedOfficerId(): ?string
    {
        return $this->assignedOfficerId;
    }

    public function getAssignedTeam(): ?string
    {
        return $this->assignedTeam;
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

    public function setIncidentType(string $incidentType): self
    {
        $this->incidentType = $incidentType;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
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

    public function setReporterName(?string $name): self
    {
        $this->reporterName = $name;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setReporterPhone(?string $phone): self
    {
        $this->reporterPhone = $phone;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setReporterAnonymous(bool $anonymous): self
    {
        $this->reporterAnonymous = $anonymous;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setAssignedOfficerId(?string $officerId): self
    {
        $this->assignedOfficerId = $officerId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setAssignedTeam(?string $team): self
    {
        $this->assignedTeam = $team;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setLocation(?string $address, ?float $lat = null, ?float $lon = null): self
    {
        $this->locationAddress = $address;
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function setWardCode(?string $wardCode): self
    {
        $this->wardCode = $wardCode;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
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
        return in_array($this->status, ['reported', 'assigned', 'in_progress'], true);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
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
            'location' => [
                'address' => $this->locationAddress,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'ward_code' => $this->wardCode,
            'reporter_id' => $this->reporterId,
            'assigned_officer_id' => $this->assignedOfficerId,
            'assigned_team' => $this->assignedTeam,
            'reported_at' => $this->reportedAt->format('Y-m-d H:i:s'),
            'resolved_at' => $this->resolvedAt?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closedAt?->format('Y-m-d H:i:s'),
            'resolution_summary' => $this->resolutionSummary,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
