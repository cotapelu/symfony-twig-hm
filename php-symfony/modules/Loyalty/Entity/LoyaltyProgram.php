<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_programs')]
#[Module(name: 'Loyalty', dependencies: ['User'])]
class LoyaltyProgram
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 50)]
    private string $programType;

    #[ORM\Column(type: 'json')]
    private array $tierRules = [];

    #[ORM\Column(type: 'json')]
    private array $rewards = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public function __construct(string $name, int $userId, string $description, string $programType)
    {
        $this->name = $name;
        $this->userId = $userId;
        $this->description = $description;
        $this->programType = $programType;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getUserId() { return $this->userId; }
    public function getDescription() { return $this->description; }
    public function getProgramType() { return $this->programType; }
    public function getTierRules() { return $this->tierRules; }
    public function getRewards() { return $this->rewards; }
    public function isActive() { return $this->isActive; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setTierRules(array $rules) { $this->tierRules = $rules; $this->updatedAt = new \DateTime(); return $this; }
    public function setRewards(array $rewards) { $this->rewards = $rewards; $this->updatedAt = new \DateTime(); return $this; }
    public function setActive(bool $active) { $this->isActive = $active; return $this; }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setProgramType(string $programType)
    {
        $this->programType = $programType;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
        return $this;
    }
}
