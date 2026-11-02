<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_rewards')]
#[Module(name: 'Loyalty', dependencies: ['User', 'Loyalty'])]
class LoyaltyReward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $programId;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'integer')]
    private int $pointsCost;

    #[ORM\Column(type: 'string', length: 50)]
    private string $rewardType;

    #[ORM\Column(type: 'json')]
    private array $details = [];

    #[ORM\Column(type: 'integer')]
    private int $stock = -1;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public function __construct(int $programId, string $name, int $pointsCost, string $rewardType)
    {
        $this->programId = $programId;
        $this->name = $name;
        $this->pointsCost = $pointsCost;
        $this->rewardType = $rewardType;
    }

    public function getId() { return $this->id; }
    public function getProgramId() { return $this->programId; }
    public function getUserId() { return $this->userId; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPointsCost() { return $this->pointsCost; }
    public function getRewardType() { return $this->rewardType; }
    public function getDetails() { return $this->details; }
    public function getStock() { return $this->stock; }
    public function isActive() { return $this->isActive; }

    public function setUserId(int $userId) { $this->userId = $userId; return $this; }
    public function setDescription(string $description) { $this->description = $description; return $this; }
    public function setDetails(array $details) { $this->details = $details; return $this; }
    public function setStock(int $stock) { $this->stock = $stock; return $this; }
    public function setActive(bool $active) { $this->isActive = $active; return $this; }
}
