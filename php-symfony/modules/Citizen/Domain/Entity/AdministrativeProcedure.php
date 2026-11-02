<?php

declare(strict_types=1);

namespace App\Citizen\Domain\Entity;

use App\AppCore\Event\EventInterface;
use App\AppCore\Event\Trait\TimestampableTrait;
use App\AppCore\Transaction\Trait\SoftDeletableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administrative_procedures')]
#[ORM\HasLifecycleCallbacks]
class AdministrativeProcedure implements EventInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'string', length: 50)]
    private string $competentAuthority;

    #[ORM\Column(type: 'json')]
    private array $requiredDocuments = [];

    #[ORM\Column(type: 'json')]
    private array $processingSteps = [];

    #[ORM\Column(type: 'integer')]
    private int $processingDays;

    #[ORM\Column(type: 'boolean')]
    private bool $isOnlineAvailable = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $legalBasis = null;

    #[ORM\Column(type: 'json')]
    private array $fees = [];

    #[ORM\OneToMany(targetEntity: ProcedureApplication::class, mappedBy: 'procedure')]
    private Collection $applications;

    public function __construct(
        string $name,
        string $code,
        string $description,
        string $category,
        string $competentAuthority,
        int $processingDays
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->category = $category;
        $this->competentAuthority = $competentAuthority;
        $this->processingDays = $processingDays;
        $this->applications = new ArrayCollection();
    }

    public function getEventName(): string
    {
        return 'administrative_procedure.updated';
    }

    public function getEventData(): array
    {
        return [
            'procedureId' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'isActive' => $this->isActive,
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->updatedAt ?? $this->createdAt ?? new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCompetentAuthority(): string
    {
        return $this->competentAuthority;
    }

    public function setCompetentAuthority(string $competentAuthority): self
    {
        $this->competentAuthority = $competentAuthority;
        return $this;
    }

    public function getRequiredDocuments(): array
    {
        return $this->requiredDocuments;
    }

    public function setRequiredDocuments(array $requiredDocuments): self
    {
        $this->requiredDocuments = $requiredDocuments;
        return $this;
    }

    public function getProcessingSteps(): array
    {
        return $this->processingSteps;
    }

    public function setProcessingSteps(array $processingSteps): self
    {
        $this->processingSteps = $processingSteps;
        return $this;
    }

    public function getProcessingDays(): int
    {
        return $this->processingDays;
    }

    public function setProcessingDays(int $processingDays): self
    {
        $this->processingDays = $processingDays;
        return $this;
    }

    public function isOnlineAvailable(): bool
    {
        return $this->isOnlineAvailable;
    }

    public function setIsOnlineAvailable(bool $isOnlineAvailable): self
    {
        $this->isOnlineAvailable = $isOnlineAvailable;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLegalBasis(): ?string
    {
        return $this->legalBasis;
    }

    public function setLegalBasis(?string $legalBasis): self
    {
        $this->legalBasis = $legalBasis;
        return $this;
    }

    public function getFees(): array
    {
        return $this->fees;
    }

    public function setFees(array $fees): self
    {
        $this->fees = $fees;
        return $this;
    }

    /**
     * @return Collection<int, ProcedureApplication>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(ProcedureApplication $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setProcedure($this);
        }
        return $this;
    }

    public function removeApplication(ProcedureApplication $application): self
    {
        if ($this->applications->removeElement($application)) {
            $application->setProcedure($this);
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'category' => $this->category,
            'competentAuthority' => $this->competentAuthority,
            'requiredDocuments' => $this->requiredDocuments,
            'processingSteps' => $this->processingSteps,
            'processingDays' => $this->processingDays,
            'isOnlineAvailable' => $this->isOnlineAvailable,
            'isActive' => $this->isActive,
            'legalBasis' => $this->legalBasis,
            'fees' => $this->fees,
            'applicationCount' => $this->applications->count(),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}