<?php

declare(strict_types=1);

namespace App\Citizen\Domain\Entity;

use App\AppCore\Event\EventInterface;
use App\AppCore\Event\Trait\TimestampableTrait;
use App\AppCore\Transaction\Trait\SoftDeletableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'citizens')]
#[ORM\HasLifecycleCallbacks]
class Citizen implements EventInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 12, unique: true)]
    private string $identityNumber;

    #[ORM\Column(type: 'string', length: 100)]
    private string $fullName;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $permanentAddress = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $currentAddress = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $ethnicity = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $religion = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $educationLevel = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $maritalStatus = null;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'household_id', referencedColumnName: 'id', nullable: true)]
    private ?Household $household = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $relationshipToHouseholdHead = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isHouseholdHead = false;

    #[ORM\Column(type: 'json')]
    private array $specialCategories = [];

    #[ORM\OneToMany(targetEntity: ResidentHistory::class, mappedBy: 'citizen')]
    private Collection $residentHistories;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'user_id', type: 'string', length: 100, nullable: true)]
    private ?string $userId = null;

    public function __construct(
        string $identityNumber,
        string $fullName,
        ?\DateTimeInterface $dateOfBirth = null,
        ?string $gender = null
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->identityNumber = $identityNumber;
        $this->fullName = $fullName;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->residentHistories = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getIdentityNumber(): string
    {
        return $this->identityNumber;
    }

    public function setIdentityNumber(string $identityNumber): self
    {
        $this->identityNumber = $identityNumber;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPermanentAddress(): ?string
    {
        return $this->permanentAddress;
    }

    public function setPermanentAddress(?string $permanentAddress): self
    {
        $this->permanentAddress = $permanentAddress;
        return $this;
    }

    public function getCurrentAddress(): ?string
    {
        return $this->currentAddress;
    }

    public function setCurrentAddress(?string $currentAddress): self
    {
        $this->currentAddress = $currentAddress;
        return $this;
    }

    public function getEthnicity(): ?string
    {
        return $this->ethnicity;
    }

    public function setEthnicity(?string $ethnicity): self
    {
        $this->ethnicity = $ethnicity;
        return $this;
    }

    public function getReligion(): ?string
    {
        return $this->religion;
    }

    public function setReligion(?string $religion): self
    {
        $this->religion = $religion;
        return $this;
    }

    public function getEducationLevel(): ?string
    {
        return $this->educationLevel;
    }

    public function setEducationLevel(?string $educationLevel): self
    {
        $this->educationLevel = $educationLevel;
        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): self
    {
        $this->occupation = $occupation;
        return $this;
    }

    public function getMaritalStatus(): ?string
    {
        return $this->maritalStatus;
    }

    public function setMaritalStatus(?string $maritalStatus): self
    {
        $this->maritalStatus = $maritalStatus;
        return $this;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;
        return $this;
    }

    public function getRelationshipToHouseholdHead(): ?string
    {
        return $this->relationshipToHouseholdHead;
    }

    public function setRelationshipToHouseholdHead(?string $relationshipToHouseholdHead): self
    {
        $this->relationshipToHouseholdHead = $relationshipToHouseholdHead;
        return $this;
    }

    public function isHouseholdHead(): bool
    {
        return $this->isHouseholdHead;
    }

    public function setIsHouseholdHead(bool $isHouseholdHead): self
    {
        $this->isHouseholdHead = $isHouseholdHead;
        return $this;
    }

    public function getSpecialCategories(): array
    {
        return $this->specialCategories;
    }

    public function setSpecialCategories(array $specialCategories): self
    {
        $this->specialCategories = $specialCategories;
        return $this;
    }

    public function addSpecialCategory(string $category): self
    {
        if (!in_array($category, $this->specialCategories, true)) {
            $this->specialCategories[] = $category;
        }
        return $this;
    }

    public function removeSpecialCategory(string $category): self
    {
        $key = array_search($category, $this->specialCategories, true);
        if ($key !== false) {
            unset($this->specialCategories[$key]);
            $this->specialCategories = array_values($this->specialCategories);
        }
        return $this;
    }

    /**
     * @return Collection<int, ResidentHistory>
     */
    public function getResidentHistories(): Collection
    {
        return $this->residentHistories;
    }

    public function addResidentHistory(ResidentHistory $residentHistory): self
    {
        if (!$this->residentHistories->contains($residentHistory)) {
            $this->residentHistories->add($residentHistory);
            $residentHistory->setCitizen($this);
        }
        return $this;
    }

    public function removeResidentHistory(ResidentHistory $residentHistory): self
    {
        if ($this->residentHistories->removeElement($residentHistory)) {
            $residentHistory->setCitizen($this);
        }
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

    /**
     * Get age based on date of birth
     */
    public function getAge(): ?int
    {
        if (!$this->dateOfBirth) {
            return null;
        }

        return (int) $this->dateOfBirth->diff(new \DateTime())->y;
    }

    /**
     * Check if citizen is elderly (60 years or older)
     */
    public function isElderly(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= 60;
    }

    /**
     * Check if citizen is a child (under 16 years)
     */
    public function isChild(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age < 16;
    }

    /**
     * Get display name with title based on gender and age
     */
    public function getDisplayName(): string
    {
        $title = '';
        if ($this->gender === 'Male') {
            if ($this->isElderly()) {
                $title = 'Ông ';
            } else {
                $title = 'Anh ';
            }
        } elseif ($this->gender === 'Female') {
            if ($this->isElderly()) {
                $title = 'Bà ';
            } else {
                $title = 'Chị ';
            }
        }

        return $title . $this->fullName;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

     public function toArray(): array
     {
         return [
             'id' => $this->id,
             'identityNumber' => $this->identityNumber,
             'fullName' => $this->fullName,
             'dateOfBirth' => $this->dateOfBirth?->format('Y-m-d'),
             'age' => $this->getAge(),
             'gender' => $this->gender,
             'phoneNumber' => $this->phoneNumber,
             'email' => $this->email,
             'permanentAddress' => $this->permanentAddress,
             'currentAddress' => $this->currentAddress,
             'ethnicity' => $this->ethnicity,
             'religion' => $this->religion,
             'educationLevel' => $this->educationLevel,
             'occupation' => $this->occupation,
             'maritalStatus' => $this->maritalStatus,
             'household' => $this->household?->getId(),
             'relationshipToHouseholdHead' => $this->relationshipToHouseholdHead,
             'isHouseholdHead' => $this->isHouseholdHead,
             'specialCategories' => $this->specialCategories,
             'displayName' => $this->getDisplayName(),
             'isElderly' => $this->isElderly(),
             'isChild' => $this->isChild(),
             'notes' => $this->notes,
             'userId' => $this->userId,
             'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
             'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
         ];
     }

    public function getEventName(): string
    {
        return 'citizen.updated';
    }

    public function getEventData(): array
    {
        return [
            'citizenId' => $this->id,
            'identityNumber' => $this->identityNumber,
            'fullName' => $this->fullName,
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->updatedAt ?? $this->createdAt ?? new \DateTime();
    }
}