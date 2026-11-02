<?php

declare(strict_types=1);

namespace App\Appointment\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'appointments')]
// #[ORM\Index(name: 'idx_appointment_citizen_id', columns: ['citizen_id'])]
// #[ORM\Index(name: 'idx_appointment_officer_id', columns: ['officer_id'])]
// #[ORM\Index(name: 'idx_appointment_status', columns: ['status'])]
// #[ORM\Index(name: 'idx_appointment_datetime', columns: ['appointment_date', 'appointment_time'])]
class Appointment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_RESCHEDULED = 'rescheduled';

    public const PURPOSE_INQUIRY = 'inquiry';
    public const PURPOSE_DOCUMENT_SUBMISSION = 'document_submission';
    public const PURPOSE_CONSULTATION = 'consultation';
    public const PURPOSE_VERIFICATION = 'verification';
    public const PURPOSE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'appointment_number', type: 'string', length: 20, unique: true)]
    private string $appointmentNumber;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_PENDING;

    // #[ORM\ManyToOne(targetEntity: Citizen::class)]
    // #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id')]
    // private ?Citizen $citizen = null;

    #[ORM\Column(name: 'citizen_name', type: 'string', length: 255)]
    private string $citizenName;

    #[ORM\Column(name: 'citizen_phone', type: 'string', length: 20)]
    private string $citizenPhone;

    #[ORM\Column(name: 'citizen_email', type: 'string', length: 255, nullable: true)]
    private ?string $citizenEmail = null;

    #[ORM\Column(name: 'citizen_cccd', type: 'string', length: 20, nullable: true)]
    private ?string $citizenCCCD = null;

    // #[ORM\ManyToOne(targetEntity: Officer::class)]
    // #[ORM\JoinColumn(name: 'officer_id', referencedColumnName: 'id')]
    // private ?Officer $officer = null;

    #[ORM\Column(name: 'officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $officerId = null;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20)]
    private string $wardCode;

    #[ORM\Column(name: 'citizen_user_id', type: 'string', length: 100, nullable: true)]
    private ?string $citizenUserId = null;

    #[ORM\Column(name: 'department', type: 'string', length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'appointment_date', type: 'datetime_immutable')]
    private DateTimeImmutable $appointmentDate;

    #[ORM\Column(name: 'appointment_time', type: 'string', length: 10)]
    private string $appointmentTime;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\Column(name: 'duration_minutes', type: 'integer', options: ['default' => 30])]
    private int $durationMinutes = 30;

    #[ORM\Column(name: 'purpose', type: 'string', length: 100)]
    private string $purpose;

    #[ORM\Column(name: 'preferred_time', type: 'string', length: 50, nullable: true)]
    private ?string $preferredTime = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'reminders_sent', type: 'json', nullable: true)]
    private ?array $remindersSent = null;

    #[ORM\Column(name: 'confirmed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(name: 'cancellation_reason', type: 'text', nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(name: 'cancel_by', type: 'string', length: 50, nullable: true)]
    private ?string $cancelBy = null;

    #[ORM\Column(name: 'rescheduled_from_id', type: 'string', length: 36, nullable: true)]
    private ?string $rescheduledFromId = null;

    // #[ORM\ManyToOne(targetEntity: Appointment::class)]
    // #[ORM\JoinColumn(name: 'rescheduled_from_id', referencedColumnName: 'id')]
    // private ?Appointment $rescheduledFrom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $officerNotes = null;

    #[ORM\Column(name: 'citizen_rating', type: 'integer', nullable: true)]
    private ?int $citizenRating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $citizenFeedback = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $id,
        string $wardCode,
        string $citizenName,
        string $citizenPhone,
        DateTimeImmutable $appointmentDate,
        string $appointmentTime,
        string $purpose
    ) {
        $this->id = $id;
        $this->appointmentNumber = $this->generateAppointmentNumber();
        $this->wardCode = $wardCode;
        $this->citizenName = $citizenName;
        $this->citizenPhone = $citizenPhone;
        $this->appointmentDate = $appointmentDate;
        $this->appointmentTime = $appointmentTime;
        $this->purpose = $purpose;
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    private function generateAppointmentNumber(): string
    {
        return 'LICH-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAppointmentNumber(): string
    {
        return $this->appointmentNumber;
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

    public function getCitizenPhone(): string
    {
        return $this->citizenPhone;
    }

    public function getCitizenEmail(): ?string
    {
        return $this->citizenEmail;
    }

    public function getCitizenCCCD(): ?string
    {
        return $this->citizenCCCD;
    }

    public function setCitizenEmail(?string $email): void
    {
        $this->citizenEmail = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setCitizenCCCD(?string $cccd): void
    {
        $this->citizenCCCD = $cccd;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getOfficer(): ?Officer
    {
        return $this->officer;
    }

    public function setOfficer(?Officer $officer): void
    {
        $this->officer = $officer;
        $this->officerId = $officer ? $officer->getId()->getValue() : null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getOfficerId(): ?string
    {
        return $this->officerId;
    }

    public function setOfficerId(?string $officerId): self
    {
        $this->officerId = $officerId;
        return $this;
    }

    public function getWardCode(): string
    {
        return $this->wardCode;
    }

    public function setWardCode(string $wardCode): self
    {
        $this->wardCode = $wardCode;
        return $this;
    }

    public function getCitizenUserId(): ?string
    {
        return $this->citizenUserId;
    }

    public function setCitizenUserId(?string $citizenUserId): self
    {
        $this->citizenUserId = $citizenUserId;
        return $this;
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

     public function getAppointmentDate(): DateTimeImmutable
    {
        return $this->appointmentDate;
    }

    public function getAppointmentTime(): string
    {
        return $this->appointmentTime;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $minutes): void
    {
        $this->durationMinutes = $minutes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    // --- Getters for timestamp fields ---
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    // --- Getters for other fields ---
    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function getCancelBy(): ?string
    {
        return $this->cancelBy;
    }

    public function getOfficerNotes(): ?string
    {
        return $this->officerNotes;
    }

    public function getCitizenRating(): ?int
    {
        return $this->citizenRating;
    }

    public function getCitizenFeedback(): ?string
    {
        return $this->citizenFeedback;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function confirm(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \LogicException('Can only confirm pending appointments');
        }
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function start(): void
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            throw new \LogicException('Can only start confirmed appointments');
        }
        $this->status = self::STATUS_IN_PROGRESS;
        $this->startedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(?string $officerNotes = null, ?int $citizenRating = null, ?string $citizenFeedback = null): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \LogicException('Can only complete in-progress appointments');
        }
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
        $this->officerNotes = $officerNotes;
        $this->citizenRating = $citizenRating;
        $this->citizenFeedback = $citizenFeedback;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(string $reason, string $cancelBy): void
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            throw new \LogicException('Cannot cancel completed or already cancelled appointments');
        }
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
        $this->cancellationReason = $reason;
        $this->cancelBy = $cancelBy;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markNoShow(): void
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            throw new \LogicException('Can only mark confirmed appointments as no-show');
        }
        $this->status = self::STATUS_NO_SHOW;
        $this->cancelledAt = new DateTimeImmutable();
        $this->cancellationReason = 'No show';
        $this->cancelBy = 'system';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFuture(): bool
    {
        $now = new DateTimeImmutable();
        $appointmentDateTime = $this->appointmentDate->setTime(
            (int)substr($this->appointmentTime, 0, 2),
            (int)substr($this->appointmentTime, 3, 2)
        );
        return $appointmentDateTime > $now;
    }

    public function isPast(): bool
    {
        return !$this->isFuture();
    }

    public function isToday(): bool
    {
        $now = new DateTimeImmutable();
        return $this->appointmentDate->format('Y-m-d') === $now->format('Y-m-d');
    }

    public function getEndTime(): DateTimeImmutable
    {
        $hour = (int)substr($this->appointmentTime, 0, 2);
        $minute = (int)substr($this->appointmentTime, 3, 2);
        $endTime = $this->appointmentDate->setTime($hour, $minute)->modify('+' . $this->durationMinutes . ' minutes');
        return $endTime;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'appointmentNumber' => $this->appointmentNumber,
            'status' => $this->status,
            'citizenName' => $this->citizenName,
            'citizenPhone' => $this->citizenPhone,
            'citizenEmail' => $this->citizenEmail,
            'citizenCCCD' => $this->citizenCCCD,
            'officer' => $this->officer?->getFullName(),
            'officerId' => $this->officerId,
            'wardCode' => $this->wardCode,
            'department' => $this->department,
            'appointmentDate' => $this->appointmentDate->format('Y-m-d'),
            'appointmentTime' => $this->appointmentTime,
            'durationMinutes' => $this->durationMinutes,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'description' => $this->description,
            'confirmedAt' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'startedAt' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'cancelledAt' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellationReason' => $this->cancellationReason,
            'cancelBy' => $this->cancelBy,
            'officerNotes' => $this->officerNotes,
            'citizenRating' => $this->citizenRating,
            'citizenFeedback' => $this->citizenFeedback,
            'isFuture' => $this->isFuture(),
            'isToday' => $this->isToday(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
