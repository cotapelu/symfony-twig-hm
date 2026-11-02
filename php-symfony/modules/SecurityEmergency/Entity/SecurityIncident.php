<?php

declare(strict_types=1);

namespace App\SecurityEmergency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'security_incidents', indexes: [
    new Index(name: 'idx_incident_type', columns: ['type']),
    new Index(name: 'idx_incident_status', columns: ['status']),
    new Index(name: 'idx_incident_created', columns: ['createdAt'])
])]
class SecurityIncident
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $type;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $reporterId;

    #[ORM\Column(name: 'assigned_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedOfficerId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct(string $type, string $status, string $reporterId, ?string $location = null, ?string $description = null)
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->type = $type;
        $this->status = $status;
        $this->reporterId = $reporterId;
        $this->location = $location;
        $this->description = $description;
        $this->createdAt = new \DateTime();
    }

    // Getters/Setters
    public function getId(): string { return $this->id; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void { $this->location = $location; }

    // Alias for assigned location (used by tests)
    public function getAssignedLocation(): ?string { return $this->location; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getAssignedOfficerId(): ?string { return $this->assignedOfficerId; }
    public function setAssignedOfficerId(?string $assignedOfficerId): void { $this->assignedOfficerId = $assignedOfficerId; }
    public function getReporterId(): string { return $this->reporterId; }
    public function setReporterId(string $reporterId): void { $this->reporterId = $reporterId; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt): void { $this->updatedAt = $updatedAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'location' => $this->location,
            'description' => $this->description,
            'reporterId' => $this->reporterId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
