<?php

declare(strict_types=1);

namespace App\Citizen\Domain\Entity;

use App\AppCore\Event\EventInterface;
use App\AppCore\Event\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'resident_histories')]
#[ORM\HasLifecycleCallbacks]
class ResidentHistory implements EventInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Citizen::class, inversedBy: 'residentHistories')]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', nullable: false)]
    private Citizen $citizen;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $effectiveDate;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $oldValue = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $newValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $recordedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(
        Citizen $citizen,
        string $type,
        \DateTimeInterface $effectiveDate,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $description = null
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->citizen = $citizen;
        $this->type = $type;
        $this->effectiveDate = $effectiveDate;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->description = $description;
    }

    public function getEventName(): string
    {
        return sprintf('resident_history.%s', $this->type);
    }

    public function getEventData(): array
    {
        return [
            'citizenId' => $this->citizen->getId(),
            'type' => $this->type,
            'effectiveDate' => $this->effectiveDate->format('Y-m-d'),
            'oldValue' => $this->oldValue,
            'newValue' => $this->newValue,
            'description' => $this->description,
            'recordedBy' => $this->recordedBy,
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->createdAt ?? $this->effectiveDate;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCitizen(): Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(Citizen $citizen): self
    {
        $this->citizen = $citizen;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getEffectiveDate(): \DateTimeInterface
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(\DateTimeInterface $effectiveDate): self
    {
        $this->effectiveDate = $effectiveDate;
        return $this;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function setOldValue(?string $oldValue): self
    {
        $this->oldValue = $oldValue;
        return $this;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): self
    {
        $this->newValue = $newValue;
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

    public function getRecordedBy(): ?string
    {
        return $this->recordedBy;
    }

    public function setRecordedBy(?string $recordedBy): self
    {
        $this->recordedBy = $recordedBy;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'citizenId' => $this->citizen->getId(),
            'type' => $this->type,
            'effectiveDate' => $this->effectiveDate->format('Y-m-d'),
            'oldValue' => $this->oldValue,
            'newValue' => $this->newValue,
            'description' => $this->description,
            'recordedBy' => $this->recordedBy,
            'notes' => $this->notes,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}