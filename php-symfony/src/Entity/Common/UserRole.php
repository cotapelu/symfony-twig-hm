<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity]
#[ORM\Table(name: 'common.user_roles')]
#[ORM\Index(name: 'idx_user_roles_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_roles_role', columns: ['role'])]
#[ORM\UniqueConstraint(name: 'uq_user_role_user_role', columns: ['user_id', 'role'])]
class UserRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $role;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $grantedBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $grantedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $userId, string $role)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->role = $role;
        $this->grantedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getGrantedBy()
    {
        return $this->grantedBy;
    }

    public function setGrantedBy(?string $grantedBy)
    {
        $this->grantedBy = $grantedBy;
        return $this;
    }

    public function getGrantedAt()
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(\DateTime $grantedAt): self
    {
        $this->grantedAt = $grantedAt;
        return $this;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
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

    public function isExpired()
    {
        return $this->expiresAt !== null && new \DateTime() > $this->expiresAt;
    }

    public function isActive()
    {
        return !$this->isExpired();
    }
}
