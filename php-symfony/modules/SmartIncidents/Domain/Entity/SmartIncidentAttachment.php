<?php

declare(strict_types=1);

namespace App\SmartIncidents\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * SmartIncident Attachment Entity
 * Represents a media attachment (image, video, audio, document) for an incident
 */
#[ORM\Entity]
#[ORM\Table(name: 'smart_incident_attachments')]
#[ORM\Index(name: 'idx_attachment_incident', columns: ['incident_id'])]
#[ORM\Index(name: 'idx_attachment_type', columns: ['file_type'])]
class SmartIncidentAttachment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SmartIncident::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'incident_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SmartIncident $incident;

    #[ORM\Column(name: 'file_type', type: 'string', length: 20)]
    private string $fileType; // 'image', 'video', 'audio', 'document'

    #[ORM\Column(name: 'file_name', type: 'string', length: 255)]
    private string $fileName;

    #[ORM\Column(name: 'file_url', type: 'string', length: 500)]
    private string $fileUrl;

    #[ORM\Column(name: 'file_size', type: 'integer')]
    private int $fileSize; // bytes

    #[ORM\Column(name: 'mime_type', type: 'string', length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'uploaded_by', type: 'string', length: 36, nullable: true)]
    private ?string $uploadedBy = null; // User ID (reporter or officer)

    #[ORM\Column(name: 'uploaded_by_role', type: 'string', length: 20, nullable: true)]
    private ?string $uploadedByRole = null; // 'reporter', 'officer', 'system'

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        SmartIncident $incident,
        string $fileType,
        string $fileName,
        string $fileUrl,
        int $fileSize
    ) {
        $this->incident = $incident;
        $this->fileType = $fileType;
        $this->fileName = $fileName;
        $this->fileUrl = $fileUrl;
        $this->fileSize = $fileSize;
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

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUploadedBy(): ?string
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?string $uploadedBy, ?string $role = null): self
    {
        $this->uploadedBy = $uploadedBy;
        $this->uploadedByRole = $role;
        return $this;
    }

    public function getUploadedByRole(): ?string
    {
        return $this->uploadedByRole;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadableFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'incidentId' => $this->incident->getId(),
            'fileType' => $this->fileType,
            'fileName' => $this->fileName,
            'fileUrl' => $this->fileUrl,
            'fileSize' => $this->fileSize,
            'readableFileSize' => $this->getReadableFileSize(),
            'mimeType' => $this->mimeType,
            'description' => $this->description,
            'uploadedBy' => $this->uploadedBy,
            'uploadedByRole' => $this->uploadedByRole,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
