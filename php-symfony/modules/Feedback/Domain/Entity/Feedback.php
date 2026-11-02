<?php

declare(strict_types=1);

namespace App\Feedback\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Entity\Officer;

#[ORM\Entity]
#[ORM\Table(name: 'feedback')]
#[ORM\Index(name: 'idx_feedback_citizen_id', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_feedback_ward_code', columns: ['ward_code'])]
#[ORM\Index(name: 'idx_feedback_status', columns: ['status'])]
class Feedback
{
    public const TYPE_SERVICE_RATING = 'service_rating';
    public const TYPE_COMPLAINT = 'complaint';
    public const TYPE_SUGGESTION = 'suggestion';
    public const TYPE_TECHNICAL_ISSUE = 'technical_issue';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'feedback_number', type: 'string', length: 20, unique: true)]
    private string $feedbackNumber;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_SUBMITTED;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', nullable: true)]
    private ?Citizen $citizen = null;

    #[ORM\Column(name: 'citizen_name', type: 'string', length: 255)]
    private string $citizenName;

    #[ORM\Column(name: 'citizen_phone', type: 'string', length: 20, nullable: true)]
    private ?string $citizenPhone = null;

    #[ORM\Column(name: 'citizen_email', type: 'string', length: 255, nullable: true)]
    private ?string $citizenEmail = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20)]
    private string $wardCode;

    #[ORM\Column(name: 'department', type: 'string', length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'service_type', type: 'string', length: 255, nullable: true)]
    private ?string $serviceType = null;

    #[ORM\Column(type: 'text')]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'rating', type: 'integer', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(name: 'priority', type: 'string', length: 20, options: ['default' => 'normal'])]
    private string $priority = 'normal';

    #[ORM\ManyToOne(targetEntity: Officer::class)]
    #[ORM\JoinColumn(name: 'assigned_to', referencedColumnName: 'id', nullable: true)]
    private ?Officer $assignedTo = null;

    #[ORM\Column(name: 'assigned_to_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedToId = null;

    #[ORM\Column(name: 'assigned_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $assignedAt = null;

    #[ORM\Column(name: 'resolution_summary', type: 'text', nullable: true)]
    private ?string $resolutionSummary = null;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'resolved_by', type: 'string', length: 36, nullable: true)]
    private ?string $resolvedBy = null;

    #[ORM\Column(name: 'citizen_satisfaction', type: 'integer', nullable: true)]
    private ?int $citizenSatisfaction = null;

    #[ORM\Column(name: 'response_deadline', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $responseDeadline = null;

    #[ORM\Column(name: 'is_anonymous', type: 'boolean', options: ['default' => false])]
    private bool $isAnonymous = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(string $id, string $type, string $wardCode, string $title, string $description, string $citizenName, ?string $citizenPhone = null, ?string $citizenEmail = null, ?int $rating = null)
    {
        $this->id = $id; $this->feedbackNumber = $this->generateFeedbackNumber(); $this->type = $type; $this->wardCode = $wardCode; $this->title = $title; $this->description = $description; $this->citizenName = $citizenName; $this->citizenPhone = $citizenPhone; $this->citizenEmail = $citizenEmail; $this->rating = $rating; $this->status = self::STATUS_SUBMITTED; $this->createdAt = new DateTimeImmutable();
        if ($type === self::TYPE_COMPLAINT) $this->priority = 'high';
    }

    private function generateFeedbackNumber(): string
    {
        return 'PH-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFeedbackNumber(): string
    {
        return $this->feedbackNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): void
    {
        $this->citizen = $citizen;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCitizenName(): string
    {
        return $this->citizenName;
    }

    public function getCitizenPhone(): ?string
    {
        return $this->citizenPhone;
    }

    public function getWardCode(): string
    {
        return $this->wardCode;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(?string $serviceType): void
    {
        $this->serviceType = $serviceType;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): void
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        $validPriorities = ['low', 'normal', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities, true)) {
            throw new \InvalidArgumentException('Invalid priority');
        }
        $this->priority = $priority;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getAssignedTo(): ?Officer
    {
        return $this->assignedTo;
    }

    public function assignTo(string $officerId): void
    {
        $this->assignedToId = $officerId;
        $this->assignedAt = new DateTimeImmutable();
        $this->status = self::STATUS_UNDER_REVIEW;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function startProcessing(): void
    {
        if ($this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \LogicException('Can only start processing feedback in under_review status');
        }
        $this->status = self::STATUS_IN_PROGRESS;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function resolve(string $summary, string $resolvedBy): void
    {
        $this->resolutionSummary = $summary;
        $this->resolvedAt = new DateTimeImmutable();
        $this->resolvedBy = $resolvedBy;
        $this->status = self::STATUS_RESOLVED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reject(string $reason): void
    {
        $this->resolutionSummary = $reason;
        $this->status = self::STATUS_REJECTED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setCitizenSatisfaction(int $satisfaction): void
    {
        if ($satisfaction < 1 || $satisfaction > 5) {
            throw new \InvalidArgumentException('Satisfaction must be between 1 and 5');
        }
        $this->citizenSatisfaction = $satisfaction;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getResponseDeadline(): ?DateTimeImmutable
    {
        return $this->responseDeadline;
    }

    public function setResponseDeadline(?DateTimeImmutable $deadline): void
    {
        $this->responseDeadline = $deadline;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setAnonymous(bool $anonymous): void
    {
        $this->isAnonymous = $anonymous;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isOverdue(): bool
    {
        if ($this->responseDeadline === null) {
            return false;
        }
        return $this->status !== self::STATUS_RESOLVED && 
               $this->status !== self::STATUS_CLOSED &&
               new DateTimeImmutable() > $this->responseDeadline;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    public function toArray(): array
    {
        return [
            'id'=>$this->id, 'feedbackNumber'=>$this->feedbackNumber, 'type'=>$this->type, 'status'=>$this->status, 'priority'=>$this->priority,
            'citizenName'=>$this->isAnonymous ? 'Ẩn danh' : $this->citizenName, 'citizenPhone'=>$this->isAnonymous ? null : $this->citizenPhone, 'citizenEmail'=>$this->isAnonymous ? null : $this->citizenEmail,
            'wardCode'=>$this->wardCode, 'department'=>$this->department, 'serviceType'=>$this->serviceType, 'title'=>$this->title, 'description'=>$this->description, 'rating'=>$this->rating, 'attachments'=>$this->attachments,
            'assignedTo'=>$this->assignedTo?->getFullName(), 'assignedAt'=>$this->assignedAt?->format('Y-m-d H:i:s'), 'resolutionSummary'=>$this->resolutionSummary, 'resolvedAt'=>$this->resolvedAt?->format('Y-m-d H:i:s'), 'resolvedBy'=>$this->resolvedBy, 'citizenSatisfaction'=>$this->citizenSatisfaction,
            'responseDeadline'=>$this->responseDeadline?->format('Y-m-d H:i:s'), 'isOverdue'=>$this->isOverdue(), 'isAnonymous'=>$this->isAnonymous, 'createdAt'=>$this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
