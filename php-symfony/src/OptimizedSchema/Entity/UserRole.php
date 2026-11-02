<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_roles')]
#[ORM\UniqueConstraint(name: 'uq_user_role', columns: ['user_id', 'role'])]
#[ORM\Index(name: 'idx_user_roles_user_id', columns: ['user_id'])]
class UserRole
{
    use IdentifiableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $role)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->role = $role;
        $this->createdAt = new \DateTime();
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}