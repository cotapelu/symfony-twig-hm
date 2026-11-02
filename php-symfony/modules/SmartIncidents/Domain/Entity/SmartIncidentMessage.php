<?php

declare(strict_types=1);

namespace App\SmartIncidents\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * SmartIncident Message Entity
 * Represents a chat message between reporter and officer
 */
#[ORM\Entity]
#[ORM\Table(name: 'smart_incident_messages')]
#[ORM\Index(name: 'idx_message_incident', columns: ['incident_id'])]
#[ORM\Index(name: 'idx_message_created', columns: ['created_at'])]
class SmartIncidentMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    private SmartIncident $incident;

    #[ORM\Column(type: 'string', length: 20)]
    private string $sender; // 'reporter', 'officer', 'system'

    #[ORM\Column(name: 'sender_id', type: 'string', length: 36, nullable: true)]
    private ?string $senderId = null;

    #[ORM\Column(name: 'sender_name', type: 'string', length: 255, nullable: true)]
    private ?string $senderName = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'attachment_url', type: 'string', length: 500, nullable: true)]
    private ?string $attachmentUrl = null;

    #[ORM\Column(name: 'attachment_type', type: 'string', length: 20, nullable: true)]
    private ?string $attachmentType = null; // 'image', 'video', 'audio', 'document'

    #[ORM\Column(name: 'is_read', type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'read_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $readAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        SmartIncident $incident,
        string $sender,
        string $content
    ) {
        $this->incident = $incident;
        $this->sender = $sender;
        $this->content = $content;
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

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getSenderId(): ?string
    {
        return $this->senderId;
    }

    public function setSenderId(?string $senderId): self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachmentUrl;
    }

    public function setAttachmentUrl(?string $url, ?string $type = null): self
    {
        $this->attachmentUrl = $url;
        $this->attachmentType = $type;
        return $this;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): self
    {
        $this->isRead = true;
        $this->readAt = new DateTimeImmutable();
        return $this;
    }

    public function getReadAt(): ?DateTimeImmutable
    {
        return $this->readAt;
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
            'sender' => $this->sender,
            'senderId' => $this->senderId,
            'senderName' => $this->senderName,
            'content' => $this->content,
            'attachmentUrl' => $this->attachmentUrl,
            'attachmentType' => $this->attachmentType,
            'isRead' => $this->isRead,
            'readAt' => $this->readAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
