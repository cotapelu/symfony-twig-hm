<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.user_sessions')]
#[ORM\Index(name: 'idx_user_sessions_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_sessions_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_user_sessions_expires_at', columns: ['expires_at'])]
class UserSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deviceInfo = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastActivityAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $tokenHash, \DateTime $expiresAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getTokenHash()
    {
        return $this->tokenHash;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent)
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getDeviceInfo()
    {
        return $this->deviceInfo;
    }

    public function setDeviceInfo(?array $deviceInfo)
    {
        $this->deviceInfo = $deviceInfo;
        return $this;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getLastActivityAt()
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTime $lastActivityAt)
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isExpired()
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function isActive()
    {
        return $this->status === 'ACTIVE' && !$this->isExpired();
    }

    public function isRevoked()
    {
        return $this->status === 'REVOKED';
    }
}
