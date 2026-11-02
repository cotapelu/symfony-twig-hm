<?php

declare(strict_types=1);

namespace App\Queue\Domain\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\Entity\Officer;
use App\Queue\Infrastructure\Repository\QueueRepository;

#[ORM\Entity(repositoryClass: QueueRepository::class)]
#[ORM\Table(name: 'queues')]
#[ORM\Index(name: 'idx_queue_ward_code', columns: ['ward_code'])]
#[ORM\Index(name: 'idx_queue_status', columns: ['status'])]
class Queue
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'ward_code', type: 'string', length: 20)]
    private string $wardCode;

    #[ORM\Column(name: 'department_name', type: 'string', length: 255, nullable: true)]
    private ?string $departmentName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'service_types', type: 'json')]
    private array $serviceTypes = [];

    #[ORM\Column(name: 'counter_count', type: 'integer', options: ['default' => 1])]
    private int $counterCount = 1;

    #[ORM\Column(name: 'current_sequence', type: 'integer', options: ['default' => 0])]
    private int $currentSequence = 0;

    #[ORM\Column(name: 'operating_hours', type: 'json', nullable: true)]
    private ?array $operatingHours = null;

    #[ORM\Column(name: 'average_service_time', type: 'integer', nullable: true)]
    private ?int $averageServiceTime = null;

    #[ORM\Column(name: 'max_tickets_per_day', type: 'integer', nullable: true)]
    private ?int $maxTicketsPerDay = null;

    #[ORM\OneToMany(mappedBy: 'queue', targetEntity: QueueTicket::class, cascade: ['persist', 'remove'])]
    private Collection $tickets;

    #[ORM\ManyToMany(targetEntity: Officer::class)]
    #[ORM\JoinTable(name: 'queue_officers')]
    private Collection $assignedOfficers;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $id,
        string $name,
        string $wardCode,
        array $serviceTypes,
        ?string $departmentName = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->wardCode = $wardCode;
        $this->serviceTypes = $serviceTypes;
        $this->departmentName = $departmentName;
        $this->status = self::STATUS_ACTIVE;
        $this->tickets = new ArrayCollection();
        $this->assignedOfficers = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getWardCode(): string
    {
        return $this->wardCode;
    }

    public function getDepartmentName(): ?string
    {
        return $this->departmentName;
    }

    public function setDepartmentName(?string $name): void
    {
        $this->departmentName = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getServiceTypes(): array
    {
        return $this->serviceTypes;
    }

    public function setServiceTypes(array $types): void
    {
        $this->serviceTypes = $types;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function hasServiceType(string $type): bool
    {
        return in_array($type, $this->serviceTypes, true);
    }

    public function addServiceType(string $type): void
    {
        if (!$this->hasServiceType($type)) {
            $this->serviceTypes[] = $type;
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function removeServiceType(string $type): void
    {
        $this->serviceTypes = array_values(array_filter(
            $this->serviceTypes,
            fn($t) => $t !== $type
        ));
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCounterCount(): int
    {
        return $this->counterCount;
    }

    public function setCounterCount(int $count): void
    {
        $this->counterCount = max(1, $count);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCurrentSequence(): int
    {
        return $this->currentSequence;
    }

    public function setCurrentSequence(int $seq): void
    {
        $this->currentSequence = $seq;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getNextSequenceNumber(): int
    {
        $this->currentSequence++;
        $this->updatedAt = new DateTimeImmutable();
        return $this->currentSequence;
    }

    public function getOperatingHours(): ?array
    {
        return $this->operatingHours;
    }

    public function setOperatingHours(?array $hours): void
    {
        $this->operatingHours = $hours;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isOpen(): bool
    {
        if (!$this->isActive() || $this->operatingHours === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N');
        $currentTime = (int)$now->format('Hi');

        if (!$this->hasScheduleForDay($dayOfWeek)) {
            return false;
        }

        $schedule = $this->getDailySchedule($dayOfWeek);
        if (!$this->isWithinOpenHours($currentTime, $schedule)) {
            return false;
        }
        if ($this->isOnBreak($currentTime, $schedule)) {
            return false;
        }

        return true;
    }

    private function hasScheduleForDay(int $dayOfWeek): bool
    {
        return isset($this->operatingHours[$dayOfWeek]);
    }

    private function getDailySchedule(int $dayOfWeek): array
    {
        $daySchedule = $this->operatingHours[$dayOfWeek];
        return [
            'open' => $this->timeToInt($daySchedule['open'] ?? '08:00'),
            'close' => $this->timeToInt($daySchedule['close'] ?? '17:00'),
            'breakStart' => isset($daySchedule['breakStart']) ? $this->timeToInt($daySchedule['breakStart']) : null,
            'breakEnd' => isset($daySchedule['breakEnd']) ? $this->timeToInt($daySchedule['breakEnd']) : null,
        ];
    }

    private function timeToInt(string $time): int
    {
        return (int)str_replace(':', '', $time);
    }

    private function isWithinOpenHours(int $currentTime, array $schedule): bool
    {
        $open = $schedule['open'];
        $close = $schedule['close'];
        return $currentTime >= $open && $currentTime < $close;
    }

    private function isOnBreak(int $currentTime, array $schedule): bool
    {
        $breakStart = $schedule['breakStart'];
        $breakEnd = $schedule['breakEnd'];
        if ($breakStart !== null && $breakEnd !== null) {
            return $currentTime >= $breakStart && $currentTime < $breakEnd;
        }
        return false;
    }

    public function getAverageServiceTime(): ?int
    {
        return $this->averageServiceTime;
    }

    public function setAverageServiceTime(?int $minutes): void
    {
        $this->averageServiceTime = $minutes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getMaxTicketsPerDay(): ?int
    {
        return $this->maxTicketsPerDay;
    }

    public function setMaxTicketsPerDay(?int $max): void
    {
        $this->maxTicketsPerDay = $max;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(QueueTicket $ticket): void
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function getWaitingTickets(): array
    {
        return $this->tickets
            ->filter(fn($t) => $t->isWaiting())
            ->toArray();
    }

    public function getInProgressTickets(): array
    {
        return $this->tickets
            ->filter(fn($t) => $t->isInProgress())
            ->toArray();
    }

    public function getWaitingTicketCount(): int
    {
        return count($this->getWaitingTickets());
    }

    public function getAverageWaitTime(): ?int
    {
        $completedTickets = $this->tickets
            ->filter(fn($t) => $t->isCompleted())
            ->toArray();

        if (empty($completedTickets)) {
            return null;
        }

        $totalWaitTime = array_sum(array_map(
            fn($t) => $t->getWaitingDuration() ?? 0,
            $completedTickets
        ));

        return (int)($totalWaitTime / count($completedTickets));
    }

    public function getAssignedOfficers(): Collection
    {
        return $this->assignedOfficers;
    }

    public function assignOfficer(Officer $officer): void
    {
        if (!$this->assignedOfficers->contains($officer)) {
            $this->assignedOfficers->add($officer);
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function removeOfficer(Officer $officer): void
    {
        $this->assignedOfficers->removeElement($officer);
        $this->updatedAt = new DateTimeImmutable();
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
            'name' => $this->name,
            'wardCode' => $this->wardCode,
            'departmentName' => $this->departmentName,
            'description' => $this->description,
            'status' => $this->status,
            'isOpen' => $this->isOpen(),
            'serviceTypes' => $this->serviceTypes,
            'counterCount' => $this->counterCount,
            'averageServiceTime' => $this->averageServiceTime,
            'maxTicketsPerDay' => $this->maxTicketsPerDay,
            'operatingHours' => $this->operatingHours,
            'waitingTicketCount' => $this->getWaitingTicketCount(),
            'averageWaitTime' => $this->getAverageWaitTime(),
            'assignedOfficers' => array_map(fn($o) => $o->getFullName(), $this->assignedOfficers->toArray()),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
