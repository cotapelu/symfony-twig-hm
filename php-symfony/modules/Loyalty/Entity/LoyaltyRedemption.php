<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[Module(name: 'Loyalty', dependencies: ['User', 'Loyalty'])]
#[ORM\Entity]
#[ORM\Table(name: 'loyalty_redemptions')]
class LoyaltyRedemption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $memberId;

    #[ORM\Column(type: 'integer')]
    private int $rewardId;

    #[ORM\Column(type: 'integer')]
    private int $pointsSpent;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $redeemedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt = null;

    public function __construct(int $memberId, int $rewardId, int $pointsSpent)
    {
        $this->memberId = $memberId;
        $this->rewardId = $rewardId;
        $this->pointsSpent = $pointsSpent;
        $this->status = 'pending';
        $this->redeemedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getMemberId() { return $this->memberId; }
    public function getRewardId() { return $this->rewardId; }
    public function getPointsSpent() { return $this->pointsSpent; }
    public function getStatus() { return $this->status; }
    public function getCode() { return $this->code; }
    public function getRedeemedAt() { return $this->redeemedAt; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function getUsedAt() { return $this->usedAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function setCode(string $code) { $this->code = $code; return $this; }
    public function setExpiresAt(?\DateTime $date) { $this->expiresAt = $date; return $this; }
    public function markAsUsed() { $this->usedAt = new \DateTime(); $this->status = 'used'; return $this; }
}
