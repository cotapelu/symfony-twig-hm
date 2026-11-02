<?php

declare(strict_types=1);

namespace App\Health\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[Module(name: 'Health', dependencies: ['User'])]
#[ORM\Entity(repositoryClass: 'App\Health\Repository\HealthProfileRepository')]
#[ORM\Table(name: 'health_profiles')]
class HealthProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId;

    #[ORM\Column(name: 'height', type: 'float', nullable: true)]
    private ?float $height = null;

    #[ORM\Column(name: 'weight', type: 'float', nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(name: 'age', type: 'integer', nullable: true)]
    private ?int $age = null;

    #[ORM\Column(name: 'blood_type', type: 'string', length: 3, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(name: 'gender', type: 'string', length: 20, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(name: 'allergies', type: 'json', nullable: false)]
    private array $allergies = [];

    #[ORM\Column(name: 'conditions', type: 'json', nullable: false)]
    private array $conditions = [];

    #[ORM\Column(name: 'medications', type: 'json', nullable: false)]
    private array $medications = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->id = null;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function getUserId(): int { return $this->userId; }
    public function getHeight(): ?float { return $this->height; }
    public function getWeight(): ?float { return $this->weight; }
    public function getAge(): ?int { return $this->age; }
    public function getBloodType(): ?string { return $this->bloodType; }
    public function getGender(): ?string { return $this->gender; }
    public function getAllergies(): array { return $this->allergies; }
    public function getConditions(): array { return $this->conditions; }
    public function getMedications(): array { return $this->medications; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setHeight(?float $height): self
    {
        if ($height !== null && $height <= 0) {
            throw new \InvalidArgumentException('Height must be positive');
        }
        $this->height = $height;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function setWeight(?float $weight): self
    {
        if ($weight !== null && $weight <= 0) {
            throw new \InvalidArgumentException('Weight must be positive');
        }
        $this->weight = $weight;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function setAge(?int $age): self
    {
        if ($age !== null && $age <= 0) {
            throw new \InvalidArgumentException('Age must be positive');
        }
        $this->age = $age;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function setBloodType(?string $type): self
    {
        if ($type !== null) {
            $valid = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (!in_array($type, $valid, true)) {
                throw new \InvalidArgumentException('Invalid blood type');
            }
        }
        $this->bloodType = $type;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function setGender(?string $gender): self
    {
        if ($gender !== null) {
            $valid = ['male', 'female', 'non-binary', 'other'];
            if (!in_array($gender, $valid, true)) {
                throw new \InvalidArgumentException('Invalid gender');
            }
        }
        $this->gender = $gender;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function addAllergy(string $allergy): self
    {
        $allergy = trim($allergy);
        if ($allergy === '') {
            throw new \InvalidArgumentException('Allergy cannot be empty');
        }
        $exists = false;
        foreach ($this->allergies as $existing) {
            if (strcasecmp($existing, $allergy) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $this->allergies[] = $allergy;
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function addCondition(string $condition): self
    {
        $condition = trim($condition);
        if ($condition === '') {
            throw new \InvalidArgumentException('Condition cannot be empty');
        }
        $exists = false;
        foreach ($this->conditions as $existing) {
            if (strcasecmp($existing, $condition) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $this->conditions[] = $condition;
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }
    public function addMedication(string $med): self
    {
        $med = trim($med);
        if ($med === '') {
            throw new \InvalidArgumentException('Medication cannot be empty');
        }
        $exists = false;
        foreach ($this->medications as $existing) {
            if (strcasecmp($existing, $med) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $this->medications[] = $med;
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBMI(): ?float
    {
        if (!$this->height || !$this->weight) return null;
        return round($this->weight / (($this->height / 100) ** 2), 1);
    }
}