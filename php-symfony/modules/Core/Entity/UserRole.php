<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_roles')]
#[ORM\HasLifecycleCallbacks]
class UserRole
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_OFFICER = 'officer';
    public const ROLE_MODERATOR = 'moderator';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $grantedAt;

    public function __construct(User $user, string $role)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->role = $role;
        $this->grantedAt = new \DateTime();
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

    public function getRole()
    {
        return $this->role;
    }

    public function setRole(string $role)
    {
        $this->role = $role;
        return $this;
    }

    public function getGrantedAt()
    {
        return $this->grantedAt;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOfficer()
    {
        return $this->role === self::ROLE_OFFICER;
    }

    public function isModerator()
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'role' => $this->role,
            'grantedAt' => $this->grantedAt->format('Y-m-d H:i:s'),
        ];
    }
}
