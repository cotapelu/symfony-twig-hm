<?php

namespace App\Rewards\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Rewards\Repository\RewardPointRepository;

#[ORM\Entity(repositoryClass: RewardPointRepository::class)]
#[ORM\Table(name: 'reward_points')]
#[ORM\Index(columns: ['user_id'], name: 'idx_reward_user')]
class RewardPoint
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\Column(type: 'integer')]
    private int $lifetimePoints = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $user)
    {
        $this->id = $this->generateId();
        $this->user = $user;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    private function generateId()
    {
        return bin2hex(random_bytes(16));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getLifetimePoints(): int
    {
        return $this->lifetimePoints;
    }

    public function setPoints(int $points)
    {
        $this->points = $points;
        $this->updateLevel();
        return $this;
    }

    public function addPoints(int $points)
    {
        $this->points += $points;
        $this->lifetimePoints += $points;
        $this->updateLevel();
        return $this;
    }

    public function removePoints(int $points)
    {
        $this->points = max(0, $this->points - $points);
        return $this;
    }

    public function getLevel()
    {
        return $this->level;
    }

    private function updateLevel()
    {
        $this->level = $this->calculateLevel($this->lifetimePoints);
    }

    private function calculateLevel(int $lifetimePoints)
    {
        if ($lifetimePoints >= 10000) return 10;
        if ($lifetimePoints >= 5000) return 9;
        if ($lifetimePoints >= 3000) return 8;
        if ($lifetimePoints >= 2000) return 7;
        if ($lifetimePoints >= 1000) return 6;
        if ($lifetimePoints >= 500) return 5;
        if ($lifetimePoints >= 250) return 4;
        if ($lifetimePoints >= 100) return 3;
        if ($lifetimePoints >= 50) return 2;
        return 1;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'points' => $this->points,
            'level' => $this->level,
            'lifetimePoints' => $this->lifetimePoints,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}