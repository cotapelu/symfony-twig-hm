<?php

declare(strict_types=1);

namespace App\Queue\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Entity\Officer;
use App\Queue\Infrastructure\Repository\QueueTicketRepository;

#[ORM\Entity(repositoryClass: QueueTicketRepository::class)]
#[ORM\Table(name: 'queue_tickets')]
#[ORM\Index(name: 'idx_queue_ticket_number', columns: ['ticket_number'])]
#[ORM\Index(name: 'idx_queue_ticket_status', columns: ['status'])]
#[ORM\Index(name: 'idx_queue_ticket_queue_id', columns: ['queue_id'])]
class QueueTicket
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_CALLED = 'called';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'ticket_number', type: 'string', length: 20)]
    private string $ticketNumber;

    #[ORM\ManyToOne(targetEntity: Queue::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'queue_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Queue $queue;

    #[ORM\Column(name: 'service_type', type: 'string', length: 100)]
    private string $serviceType;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', nullable: true)]
    private ?Citizen $citizen = null;

    #[ORM\Column(name: 'citizen_name', type: 'string', length: 255)]
    private string $citizenName;

    #[ORM\Column(name: 'citizen_phone', type: 'string', length: 20, nullable: true)]
    private ?string $citizenPhone = null;

    #[ORM\ManyToOne(targetEntity: Officer::class)]
    #[ORM\JoinColumn(name: 'served_by_officer_id', referencedColumnName: 'id', nullable: true)]
    private ?Officer $servedByOfficer = null;

    #[ORM\Column(name: 'counter_number', type: 'string', length: 20, nullable: true)]
    private ?string $counterNumber = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_WAITING;

    #[ORM\Column(name: 'priority', type: 'integer', options: ['default' => 0])]
    private int $priority = 0;

    #[ORM\Column(name: 'estimated_wait_time', type: 'integer', nullable: true)]
    private ?int $estimatedWaitTime = null;

    #[ORM\Column(name: 'called_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $calledAt = null;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $id,
        Queue $queue,
        string $serviceType,
        string $citizenName,
        ?string $citizenPhone = null,
        int $priority = 0,
        ?string $ticketNumber = null
    ) {
        $this->id = $id;
        $this->queue = $queue;
        $this->serviceType = $serviceType;
        $this->citizenName = $citizenName;
        $this->citizenPhone = $citizenPhone;
        $this->priority = $priority;
        if ($ticketNumber !== null) {
            $this->ticketNumber = $ticketNumber;
        } else {
            $this->ticketNumber = $this->generateTicketNumber();
        }
        $this->status = self::STATUS_WAITING;
        $this->createdAt = new DateTimeImmutable();
    }

    private function generateTicketNumber(): string
    {
        $prefix = mb_strtoupper($this->serviceType, 'UTF-8');
        $sequence = $this->queue->getNextSequenceNumber();
        return $prefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTicketNumber(): string
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber(string $ticketNumber): void
    {
        $this->ticketNumber = $ticketNumber;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
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

    public function setCitizenName(string $name): void
    {
        $this->citizenName = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCitizenPhone(): ?string
    {
        return $this->citizenPhone;
    }

    public function setCitizenPhone(?string $phone): void
    {
        $this->citizenPhone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getEstimatedWaitTime(): ?int
    {
        return $this->estimatedWaitTime;
    }

    public function setEstimatedWaitTime(?int $minutes): void
    {
        $this->estimatedWaitTime = $minutes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCalledAt(): ?DateTimeImmutable
    {
        return $this->calledAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getServedByOfficer(): ?Officer
    {
        return $this->servedByOfficer;
    }

    public function getCounterNumber(): ?string
    {
        return $this->counterNumber;
    }

    public function call(Officer $officer, string $counterNumber): void
    {
        $this->status = self::STATUS_CALLED;
        $this->servedByOfficer = $officer;
        $this->counterNumber = $counterNumber;
        $this->calledAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function start(): void
    {
        if ($this->status !== self::STATUS_CALLED) {
            throw new \LogicException('Can only start a called ticket');
        }
        $this->status = self::STATUS_IN_PROGRESS;
        $this->startedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(?string $notes = null): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \LogicException('Can only complete a ticket in progress');
        }
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(?string $reason = null): void
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            throw new \LogicException('Cannot cancel completed or already cancelled ticket');
        }
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
        $this->notes = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function skip(): void
    {
        if ($this->status !== self::STATUS_CALLED) {
            throw new \LogicException('Can only skip a called ticket');
        }
        $this->status = self::STATUS_SKIPPED;
        $this->calledAt = null;
        $this->counterNumber = null;
        $this->servedByOfficer = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isCalled(): bool
    {
        return $this->status === self::STATUS_CALLED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function getWaitingDuration(): ?int
    {
        if ($this->calledAt === null) {
            return null;
        }
        return (int)($this->calledAt->getTimestamp() - $this->createdAt->getTimestamp()) / 60;
    }

    public function getServiceDuration(): ?int
    {
        if ($this->completedAt === null || $this->startedAt === null) {
            return null;
        }
        return (int)($this->completedAt->getTimestamp() - $this->startedAt->getTimestamp()) / 60;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ticketNumber' => $this->ticketNumber,
            'queueId' => $this->queue->getId(),
            'queueName' => $this->queue->getName(),
            'serviceType' => $this->serviceType,
            'citizenName' => $this->citizenName,
            'citizenPhone' => $this->citizenPhone,
            'status' => $this->status,
            'priority' => $this->priority,
            'estimatedWaitTime' => $this->estimatedWaitTime,
            'calledAt' => $this->calledAt?->format('Y-m-d H:i:s'),
            'startedAt' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'counterNumber' => $this->counterNumber,
            'servedByOfficer' => $this->servedByOfficer?->getFullName(),
            'waitingDuration' => $this->getWaitingDuration(),
            'serviceDuration' => $this->getServiceDuration(),
            'notes' => $this->notes,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
