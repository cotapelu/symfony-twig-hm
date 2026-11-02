<?php

namespace App\Rewards\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'achievement_badges')]
#[ORM\Index(columns: ['user_id'], name: 'idx_badge_user')]
class AchievementBadge
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'earned_at', type: 'datetime')]
    private \DateTime $earnedAt;

    public function __construct(
        User $user,
        string $type,
        string $name,
        ?string $description = null,
        ?string $icon = null,
        ?array $metadata = null
    ) {
        $this->id = $this->generateId();
        $this->user = $user;
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->icon = $icon;
        $this->metadata = $metadata;
        $this->earnedAt = new \DateTime();
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

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEarnedAt()
    {
        return $this->earnedAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'metadata' => $this->metadata,
            'earnedAt' => $this->earnedAt->format('Y-m-d H:i:s'),
        ];
    }
}