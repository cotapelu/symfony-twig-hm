<?php

declare(strict_types=1);

namespace App\Population\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Population\Repository\PopulationRecordRepository;

#[ORM\Entity(repositoryClass: PopulationRecordRepository::class)]
#[ORM\Table(name: 'population_records')]
class PopulationRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'date')]
    private \DateTime $birthDate;

    #[ORM\Column(type: 'string', length: 10)]
    private string $gender;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'string', length: 50)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $citizenId;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
        return $this;
    }

    public function getBirthDate()
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate)
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function setGender(string $gender)
    {
        $this->gender = $gender;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(string $address)
    {
        $this->address = $address;
        return $this;
    }

    public function getHouseholdId()
    {
        return $this->householdId;
    }

    public function setHouseholdId(string $householdId)
    {
        $this->householdId = $householdId;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getCitizenId()
    {
        return $this->citizenId;
    }

    public function setCitizenId(string $citizenId)
    {
        $this->citizenId = $citizenId;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_date' => $this->birthDate instanceof \DateTime ? $this->birthDate->format('Y-m-d') : null,
            'gender' => $this->gender,
            'address' => $this->address,
            'household_id' => $this->householdId,
            'status' => $this->status,
            'type' => $this->type,
            'citizen_id' => $this->citizenId,
            'created_at' => $this->createdAt instanceof \DateTime ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt instanceof \DateTime ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
