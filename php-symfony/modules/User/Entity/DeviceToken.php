<?php

declare(strict_types=1);

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Device Token Entity for Push Notifications.
 */
#[ORM\Entity]
#[ORM\Table(name: 'device_tokens')]
class DeviceToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $token;

    #[ORM\Column(type: 'string', length: 20)]
    private string $platform; // 'web', 'ios', 'android'

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    private ?\DateTime $lastUsedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive;

    public function __construct(
        string $userId,
        string $token,
        string $platform,
    ) {
        $this->id = bin2hex(random_bytes(18)); // 36-char string
        $this->userId = $userId;
        $this->token = $token;
        $this->platform = $platform;
        $this->createdAt = new \DateTime();
        $this->lastUsedAt = null;
        $this->isActive = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getLastUsedAt()
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTime $lastUsedAt)
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }
}
