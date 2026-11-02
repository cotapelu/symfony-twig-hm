<?php

declare(strict_types=1);

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_refresh_tokens')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $deviceId;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $token;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTime $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    private ?\DateTime $revokedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    public function __construct(string $userId, string $deviceId, ?string $userAgent = null)
    {
        $this->id = bin2hex(random_bytes(32));
        $this->userId = $userId;
        $this->deviceId = $deviceId;
        $this->token = bin2hex(random_bytes(32));
        $this->expiresAt = new \DateTime('+30 days');
        $this->revokedAt = null;
        $this->createdAt = new \DateTime();
        $this->userAgent = $userAgent;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isExpired()
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function isRevoked()
    {
        return null !== $this->revokedAt;
    }

    public function revoke()
    {
        $this->revokedAt = new \DateTime();
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }
}
