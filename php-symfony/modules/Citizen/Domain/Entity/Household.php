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
#[ORM\Table(name: 'households')]
#[ORM\HasLifecycleCallbacks]
class Household implements EventInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $householdNumber;

    #[ORM\Column(type: 'string', length: 200)]
    private string $address;

    #[ORM\Column(type: 'string', length: 50)]
    private string $ward;

    #[ORM\Column(type: 'string', length: 50)]
    private string $district;

    #[ORM\Column(type: 'string', length: 50)]
    private string $province;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $householdType = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(type: 'json')]
    private array $economicConditions = [];

    #[ORM\Column(type: 'json')]
    private array $housingConditions = [];

    #[ORM\OneToMany(targetEntity: Citizen::class, mappedBy: 'household')]
    private Collection $members;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'household_head_id', referencedColumnName: 'id', nullable: true)]
    private ?Citizen $householdHead = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(
        string $householdNumber,
        string $address,
        string $ward,
        string $district,
        string $province
    ) {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->householdNumber = $householdNumber;
        $this->address = $address;
        $this->ward = $ward;
        $this->district = $district;
        $this->province = $province;
        $this->members = new ArrayCollection();
        $this->registrationDate = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHouseholdNumber(): string
    {
        return $this->householdNumber;
    }

    public function setHouseholdNumber(string $householdNumber): self
    {
        $this->householdNumber = $householdNumber;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getWard(): string
    {
        return $this->ward;
    }

    public function setWard(string $ward): self
    {
        $this->ward = $ward;
        return $this;
    }

    public function getDistrict(): string
    {
        return $this->district;
    }

    public function setDistrict(string $district): self
    {
        $this->district = $district;
        return $this;
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function setProvince(string $province): self
    {
        $this->province = $province;
        return $this;
    }

    public function getFullAddress(): string
    {
        return sprintf('%s, %s, %s, %s', $this->address, $this->ward, $this->district, $this->province);
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

    public function getHouseholdType(): ?string
    {
        return $this->householdType;
    }

    public function setHouseholdType(?string $householdType): self
    {
        $this->householdType = $householdType;
        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(?\DateTimeInterface $registrationDate): self
    {
        $this->registrationDate = $registrationDate;
        return $this;
    }

    public function getEconomicConditions(): array
    {
        return $this->economicConditions;
    }

    public function setEconomicConditions(array $economicConditions): self
    {
        $this->economicConditions = $economicConditions;
        return $this;
    }

    public function getHousingConditions(): array
    {
        return $this->housingConditions;
    }

    public function setHousingConditions(array $housingConditions): self
    {
        $this->housingConditions = $housingConditions;
        return $this;
    }

    /**
     * @return Collection<int, Citizen>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Citizen $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setHousehold($this);
        }
        return $this;
    }

    public function removeMember(Citizen $member): self
    {
        if ($this->members->removeElement($member)) {
            if ($member->getHousehold() === $this) {
                $member->setHousehold(null);
            }
        }
        return $this;
    }

    public function getHouseholdHead(): ?Citizen
    {
        return $this->householdHead;
    }

    public function setHouseholdHead(?Citizen $householdHead): self
    {
        $this->householdHead = $householdHead;
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

    public function getMemberCount(): int
    {
        return $this->members->count();
    }

    public function getElderlyCount(): int
    {
        return $this->members->filter(fn(Citizen $citizen) => $citizen->isElderly())->count();
    }

    public function getChildCount(): int
    {
        return $this->members->filter(fn(Citizen $citizen) => $citizen->isChild())->count();
    }

    public function getPovertyStatus(): string
    {
        $economicConditions = $this->economicConditions;
        
        if (isset($economicConditions['isPoorHousehold']) && $economicConditions['isPoorHousehold']) {
            if (isset($economicConditions['povertyLevel'])) {
                return $economicConditions['povertyLevel'];
            }
            return 'Poor';
        }
        
        return 'Non-poor';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'householdNumber' => $this->householdNumber,
            'address' => $this->address,
            'fullAddress' => $this->getFullAddress(),
            'ward' => $this->ward,
            'district' => $this->district,
            'province' => $this->province,
            'phoneNumber' => $this->phoneNumber,
            'householdType' => $this->householdType,
            'registrationDate' => $this->registrationDate?->format('Y-m-d'),
            'economicConditions' => $this->economicConditions,
            'housingConditions' => $this->housingConditions,
            'memberCount' => $this->getMemberCount(),
            'elderlyCount' => $this->getElderlyCount(),
            'childCount' => $this->getChildCount(),
            'povertyStatus' => $this->getPovertyStatus(),
            'householdHead' => $this->householdHead?->toArray(),
            'notes' => $this->notes,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'household.updated';
    }

    public function getEventData(): array
    {
        return [
            'householdId' => $this->id,
            'householdNumber' => $this->householdNumber,
            'address' => $this->address,
            'memberCount' => $this->getMemberCount(),
        ];
    }

    public function getEventTimestamp(): \DateTimeInterface
    {
        return $this->updatedAt ?? $this->createdAt ?? new \DateTime();
    }
}