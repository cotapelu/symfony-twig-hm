<?php

namespace App\Groups\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group_members')]
#[ORM\Index(columns: ['user_id'], name: 'idx_group_member_user')]
#[ORM\Index(columns: ['group_id'], name: 'idx_group_member_group')]
#[ORM\Index(columns: ['role'], name: 'idx_group_member_role')]
class GroupMember
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(type: 'boolean')]
    private bool $isMuted = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isBanned = false;

    #[ORM\Column(name: 'joined_at', type: 'datetime')]
    private \DateTime $joinedAt;

    #[ORM\Column(name: 'last_active_at', type: 'datetime')]
    private \DateTime $lastActiveAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        User $user,
        ?Group $group = null,
        string $role = self::ROLE_MEMBER,
        ?array $metadata = null
    ) {
        $this->id = $this->generateId();
        $this->group = $group;
        $this->user = $user;
        $this->role = $role;
        $this->joinedAt = new \DateTime();
        $this->lastActiveAt = new \DateTime();
        $this->metadata = $metadata;
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

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(?Group $group)
    {
        $this->group = $group;
        return $this;
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

    public function isOwner()
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator()
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isMember()
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function canManageGroup()
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    public function isMuted()
    {
        return $this->isMuted;
    }

    public function setMuted(bool $isMuted)
    {
        $this->isMuted = $isMuted;
        return $this;
    }

    public function isBanned()
    {
        return $this->isBanned;
    }

    public function setBanned(bool $isBanned)
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTime $joinedAt)
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(\DateTime $lastActiveAt)
    {
        $this->lastActiveAt = $lastActiveAt;
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

    public function isActive()
    {
        return $this->isActive && !$this->isBanned;
    }

    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'groupId' => $this->group?->getId(),
            'userId' => $this->user->getId(),
            'userEmail' => $this->user->getEmail(),
            'role' => $this->role,
            'isMuted' => $this->isMuted,
            'isBanned' => $this->isBanned,
            'joinedAt' => $this->joinedAt->format('Y-m-d H:i:s'),
            'lastActiveAt' => $this->lastActiveAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}