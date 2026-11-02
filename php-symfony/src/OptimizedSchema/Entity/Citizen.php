<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use App\OptimizedSchema\Entity\Trait\StatusAwareTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'citizens')]
#[ORM\Index(name: 'idx_citizens_household_id', columns: ['household_id'])]
#[ORM\Index(name: 'idx_citizens_identity_number', columns: ['identity_number'])]
class Citizen
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use StatusAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'identity_number', type: 'string', length: 20, unique: true, nullable: true)]
    private ?string $identityNumber;

    #[ORM\Column(name: 'household_id', type: 'uuid', nullable: true)]
    private ?string $householdId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->status = 'active';
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

    public function getIdentityNumber(): ?string
    {
        return $this->identityNumber;
    }

    public function setIdentityNumber(?string $identityNumber): self
    {
        $this->identityNumber = $identityNumber;
        return $this;
    }

    public function getHouseholdId(): ?string
    {
        return $this->householdId;
    }

    public function setHouseholdId(?string $householdId): self
    {
        $this->householdId = $householdId;
        return $this;
    }
}