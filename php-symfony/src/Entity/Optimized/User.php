<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.users')]
#[ORM\Index(name: 'idx_users_email', columns: ['email'])]
#[ORM\Index(name: 'idx_users_phone', columns: ['phone'])]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string')]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastLoginAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $email, string $passwordHash)
    {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function setEmail(string $email) { $this->email = $email; return $this; }
    public function getPhone() { return $this->phone; }
    public function setPhone(?string $phone) { $this->phone = $phone; return $this; }
    public function getPasswordHash() { return $this->passwordHash; }
    public function setPasswordHash(string $hash) { $this->passwordHash = $hash; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getRoles() { return $this->roles; }
    public function setRoles(array $roles) { $this->roles = $roles; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getLastLoginAt() { return $this->lastLoginAt; }
    public function setLastLoginAt(?\DateTime $at) { $this->lastLoginAt = $at; return $this; }
    public function getMetadata() { return $this->metadata; }
    public function setMetadata(?array $m) { $this->metadata = $m; return $this; }

    public function isActive() { return $this->status === 'active'; }
}