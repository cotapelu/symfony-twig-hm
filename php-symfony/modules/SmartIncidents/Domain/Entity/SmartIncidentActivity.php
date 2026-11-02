<?php

declare(strict_types=1);

namespace App\SmartIncidents\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * SmartIncident Activity Entity
 * Tracks all activity changes on an incident
 */
#[ORM\Entity]
#[ORM\Table(name: 'smart_incident_activities')]
#[ORM\Index(name: 'idx_activity_incident', columns: ['incident_id'])]
#[ORM\Index(name: 'idx_activity_type', columns: ['activity_type'])]
#[ORM\Index(name: 'idx_activity_created', columns: ['created_at'])]
class SmartIncidentActivity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    private SmartIncident $incident;

    #[ORM\Column(name: 'activity_type', type: 'string', length: 50)]
    private string $activityType;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\Column(name: 'performed_by', type: 'string', length: 36)]
    private string $performedBy;

    #[ORM\Column(name: 'performed_by_name', type: 'string', length: 255)]
    private string $performedByName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metadataJson = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        SmartIncident $incident,
        string $activityType,
        string $description,
        string $performedBy,
        string $performedByName
    ) {
        $this->incident = $incident;
        $this->activityType = $activityType;
        $this->description = $description;
        $this->performedBy = $performedBy;
        $this->performedByName = $performedByName;
        $this->createdAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncident(): SmartIncident
    {
        return $this->incident;
    }

    public function setIncident(SmartIncident $incident): self
    {
        $this->incident = $incident;
        return $this;
    }

    public function getActivityType(): string
    {
        return $this->activityType;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPerformedBy(): string
    {
        return $this->performedBy;
    }

    public function getPerformedByName(): string
    {
        return $this->performedByName;
    }

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
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'incidentId' => $this->incident->getId(),
            'activityType' => $this->activityType,
            'description' => $this->description,
            'performedBy' => $this->performedBy,
            'performedByName' => $this->performedByName,
            'metadata' => $this->getMetadata(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    // Static factory methods for common activities
    public static function createCreatedActivity(
        SmartIncident $incident,
        string $performedBy,
        string $performedByName
    ): self {
        return new self(
            $incident,
            'created',
            'Incident reported',
            $performedBy,
            $performedByName
        );
    }

    public static function createStatusChangedActivity(
        SmartIncident $incident,
        string $oldStatus,
        string $newStatus,
        string $performedBy,
        string $performedByName
    ): self {
        return new self(
            $incident,
            'status_changed',
            sprintf('Status changed from %s to %s', $oldStatus, $newStatus),
            $performedBy,
            $performedByName
        );
    }

    public static function createAssignedActivity(
        SmartIncident $incident,
        string $officerId,
        ?string $team,
        string $performedBy,
        string $performedByName
    ): self {
        $description = sprintf('Assigned to officer %s', $officerId);
        if ($team) {
            $description .= sprintf(' in team %s', $team);
        }
        
        return new self(
            $incident,
            'assigned',
            $description,
            $performedBy,
            $performedByName
        );
    }

    public static function createResolvedActivity(
        SmartIncident $incident,
        string $summary,
        string $performedBy,
        string $performedByName
    ): self {
        return new self(
            $incident,
            'resolved',
            'Incident resolved: ' . $summary,
            $performedBy,
            $performedByName
        );
    }

    public static function createEscalatedActivity(
        SmartIncident $incident,
        string $reason,
        string $performedBy,
        string $performedByName
    ): self {
        return new self(
            $incident,
            'escalated',
            'Incident escalated: ' . $reason,
            $performedBy,
            $performedByName
        );
    }

    public static function createMessageSentActivity(
        SmartIncident $incident,
        string $sender,
        string $senderName
    ): self {
        return new self(
            $incident,
            'message_sent',
            sprintf('Message sent by %s', $senderName),
            $sender,
            $senderName
        );
    }

    public static function createAttachmentAddedActivity(
        SmartIncident $incident,
        int $attachmentId,
        string $fileType,
        string $performedBy,
        string $performedByName
    ): self {
        return new self(
            $incident,
            'attachment_added',
            sprintf('Attachment added: %s #%d', $fileType, $attachmentId),
            $performedBy,
            $performedByName
        );
    }
}
