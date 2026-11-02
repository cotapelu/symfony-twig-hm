<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use App\Government\Domain\ValueObject\DocumentId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gov_documents')]
class Document
{
    public const TYPE_DECREE = 'decree';
    public const TYPE_DECISION = 'decision';
    public const TYPE_CIRCULAR = 'circular';
    public const TYPE_NOTICE = 'notice';
    public const TYPE_REPORT = 'report';
    public const TYPE_OTHER = 'other';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'document_number', type: 'string', length: 50, nullable: true)]
    private ?string $documentNumber;

    #[ORM\Column(name: 'issued_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $issuedDate;

    #[ORM\Column(name: 'effective_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $effectiveDate;

    #[ORM\Column(name: 'issued_by', type: 'string', length: 100)]
    private string $issuedBy;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'json')]
    private array $attachments;

    #[ORM\Column(type: 'json')]
    private array $tags;

    #[ORM\Column(name: 'signed_by', type: 'string', length: 100, nullable: true)]
    private ?string $signedBy;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary;
    
    public function __construct(
        string $title,
        string $type,
        string $content,
        string $issuedBy,
        ?string $documentNumber = null,
        ?\DateTimeImmutable $issuedDate = null,
        ?\DateTimeImmutable $effectiveDate = null,
        ?string $signedBy = null,
        ?string $summary = null
    ) {
        $this->id = (new DocumentId())->toString();
        $this->title = $title;
        $this->type = $type;
        $this->content = $content;
        $this->issuedBy = $issuedBy;
        $this->documentNumber = $documentNumber;
        $this->issuedDate = $issuedDate;
        $this->effectiveDate = $effectiveDate;
        $this->signedBy = $signedBy;
        $this->summary = $summary;
        $this->status = self::STATUS_DRAFT;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->attachments = [];
        $this->tags = [];
    }
    
    public function getId(): DocumentId
    {
        return new DocumentId($this->id);
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function setType(string $type): void
    {
        $this->type = $type;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getContent(): string
    {
        return $this->content;
    }
    
    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }
    
    public function setDocumentNumber(?string $documentNumber): void
    {
        $this->documentNumber = $documentNumber;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getIssuedDate(): ?\DateTimeImmutable
    {
        return $this->issuedDate;
    }
    
    public function setIssuedDate(?\DateTimeImmutable $issuedDate): void
    {
        $this->issuedDate = $issuedDate;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getEffectiveDate(): ?\DateTimeImmutable
    {
        return $this->effectiveDate;
    }
    
    public function setEffectiveDate(?\DateTimeImmutable $effectiveDate): void
    {
        $this->effectiveDate = $effectiveDate;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getIssuedBy(): string
    {
        return $this->issuedBy;
    }
    
    public function setIssuedBy(string $issuedBy): void
    {
        $this->issuedBy = $issuedBy;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function publish(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function archive(): void
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function getAttachments(): array
    {
        return $this->attachments;
    }
    
    public function addAttachment(string $fileName, string $filePath, string $mimeType): void
    {
        $this->attachments[] = [
            'fileName' => $fileName,
            'filePath' => $filePath,
            'mimeType' => $mimeType,
            'uploadedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function removeAttachment(string $fileName): bool
    {
        foreach ($this->attachments as $index => $attachment) {
            if ($attachment['fileName'] === $fileName) {
                unset($this->attachments[$index]);
                $this->attachments = array_values($this->attachments);
                $this->updatedAt = new \DateTimeImmutable();
                return true;
            }
        }
        return false;
    }
    
    public function getTags(): array
    {
        return $this->tags;
    }
    
    public function addTag(string $tag): void
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    
    public function removeTag(string $tag): bool
    {
        $index = array_search($tag, $this->tags);
        if ($index !== false) {
            unset($this->tags[$index]);
            $this->tags = array_values($this->tags);
            $this->updatedAt = new \DateTimeImmutable();
            return true;
        }
        return false;
    }
    
    public function getSignedBy(): ?string
    {
        return $this->signedBy;
    }
    
    public function setSignedBy(?string $signedBy): void
    {
        $this->signedBy = $signedBy;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function getSummary(): ?string
    {
        return $this->summary;
    }
    
    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Workflow methods
    public function submitForReview(): void
    {
        if ($this->status !== self::STATUS_DRAFT && $this->status !== self::STATUS_REJECTED) {
            throw new \InvalidArgumentException('Only draft or rejected documents can be submitted for review');
        }
        $this->status = self::STATUS_PENDING;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function approve(?string $approvedBy = null, ?string $comments = null): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending documents can be approved');
        }
        $this->status = self::STATUS_APPROVED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(?string $rejectedBy = null, ?string $reason = null): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending documents can be rejected');
        }
        $this->status = self::STATUS_REJECTED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getHistory(): array
    {
        return [
            [
                'action' => 'created',
                'status' => self::STATUS_DRAFT,
                'timestamp' => $this->createdAt->format('Y-m-d H:i:s'),
            ],
            [
                'action' => 'current',
                'status' => $this->status,
                'timestamp' => $this->updatedAt->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
