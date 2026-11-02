<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.citizens')]
#[ORM\Index(name: 'idx_citizens_identity_number', columns: ['identity_number'])]
#[ORM\Index(name: 'idx_citizens_household_id', columns: ['household_id'])]
#[ORM\Index(name: 'idx_citizens_status', columns: ['status'])]
class Citizen
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $identityNumber;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $householdId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $name, string $identityNumber)
    {
        $this->id = $id;
        $this->name = $name;
        $this->identityNumber = $identityNumber;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getIdentityNumber()
    {
        return $this->identityNumber;
    }

    public function setIdentityNumber(string $identityNumber)
    {
        $this->identityNumber = $identityNumber;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getHouseholdId()
    {
        return $this->householdId;
    }

    public function setHouseholdId(?string $householdId)
    {
        $this->householdId = $householdId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isSuspended()
    {
        return $this->status === 'SUSPENDED';
    }

    public function isDeleted()
    {
        return $this->status === 'DELETED';
    }

    public function hasHousehold()
    {
        return $this->householdId !== null;
    }
}
