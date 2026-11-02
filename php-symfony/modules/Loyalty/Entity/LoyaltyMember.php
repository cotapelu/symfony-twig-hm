<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_members')]
#[Module(name: 'Loyalty', dependencies: ['User', 'Loyalty'])]
class LoyaltyMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'integer')]
    private int $programId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $tier;

    #[ORM\Column(type: 'integer')]
    private int $points;

    #[ORM\Column(type: 'integer')]
    private int $lifetimePoints;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $joinedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastActivityAt = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 0;

    public function __construct(int $userId, int $programId)
    {
        $this->userId = $userId;
        $this->programId = $programId;
        $this->tier = 'bronze';
        $this->points = 0;
        $this->lifetimePoints = 0;
        $this->joinedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getProgramId() { return $this->programId; }
    public function getTier() { return $this->tier; }
    public function getPoints() { return $this->points; }
    public function getLifetimePoints() { return $this->lifetimePoints; }
    public function getJoinedAt() { return $this->joinedAt; }
    public function getLastActivityAt() { return $this->lastActivityAt; }
    public function getVersion() { return $this->version; }

    public function addPoints(int $points)
    {
        $this->points += $points;
        $this->lifetimePoints += $points;
        $this->lastActivityAt = new \DateTime();
        return $this;
    }

    public function deductPoints(int $points)
    {
        $this->points = max(0, $this->points - $points);
        return $this;
    }

    public function setTier(string $tier) { $this->tier = $tier; return $this; }
}
