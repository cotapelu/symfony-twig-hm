<?php

declare(strict_types=1);

namespace App\Citizen\Domain\Entity;

use App\AppCore\Event\EventInterface;
use App\AppCore\Event\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'application_status_histories')]
#[ORM\HasLifecycleCallbacks]
class ApplicationStatusHistory implements EventInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ProcedureApplication::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false)]
    private ProcedureApplication $application;

    #[ORM\Column(type: 'string', length: 20)]
    private string $fromStatus;

    #[ORM\Column(type: 'string', length: 20)]
    private string $toStatus;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $changedBy = null;

    public function __construct(
        ProcedureApplication $application,
        string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $changedBy = null
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->application = $application;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->reason = $reason;
        $this->changedBy = $changedBy;
    }

    public function getEventName(): string
    {
        return 'application_status.changed';
    }

    public function getEventData(): array
    {
        return [
            'historyId' => $this->id,
            'applicationId' => $this->application->getId(),
            'fromStatus' => $this->fromStatus,
            'toStatus' => $this->toStatus,
            'changedBy' => $this->changedBy,
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->createdAt ?? new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getApplication(): ProcedureApplication
    {
        return $this->application;
    }

    public function setApplication(ProcedureApplication $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getFromStatus(): string
    {
        return $this->fromStatus;
    }

    public function setFromStatus(string $fromStatus): self
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): string
    {
        return $this->toStatus;
    }

    public function setToStatus(string $toStatus): self
    {
        $this->toStatus = $toStatus;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }

    public function setChangedBy(?string $changedBy): self
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'applicationId' => $this->application->getId(),
            'fromStatus' => $this->fromStatus,
            'toStatus' => $this->toStatus,
            'reason' => $this->reason,
            'changedBy' => $this->changedBy,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}