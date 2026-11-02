<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_accounts')]
#[ORM\HasLifecycleCallbacks]
class LoyaltyAccount
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: LoyaltyProgram::class)]
    #[ORM\JoinColumn(name: 'program_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private LoyaltyProgram $program;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $tier = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $user, LoyaltyProgram $program)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->program = $program;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getProgram()
    {
        return $this->program;
    }

    public function setProgram(LoyaltyProgram $program)
    {
        $this->program = $program;
        return $this;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function setPoints(int $points)
    {
        $this->points = $points;
        return $this;
    }

    public function addPoints(int $points)
    {
        $this->points += $points;
        return $this;
    }

    public function deductPoints(int $points)
    {
        $this->points = max(0, $this->points - $points);
        return $this;
    }

    public function getTier()
    {
        return $this->tier;
    }

    public function setTier(?string $tier)
    {
        $this->tier = $tier;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
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

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'programId' => $this->program->getId(),
            'points' => $this->points,
            'tier' => $this->tier,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
