<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.device_tokens')]
#[ORM\Index(name: 'idx_device_tokens_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_device_tokens_token', columns: ['token'])]
#[ORM\Index(name: 'idx_device_tokens_platform', columns: ['platform'])]
class DeviceToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 50)]
    private string $platform;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deviceModel = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $osVersion = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $appVersion = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastUsedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $token, string $platform)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->platform = $platform;
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

    public function getToken()
    {
        return $this->token;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function getDeviceName()
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName)
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    public function setDeviceModel(?string $deviceModel)
    {
        $this->deviceModel = $deviceModel;
        return $this;
    }

    public function getOsVersion()
    {
        return $this->osVersion;
    }

    public function setOsVersion(?string $osVersion)
    {
        $this->osVersion = $osVersion;
        return $this;
    }

    public function getAppVersion()
    {
        return $this->appVersion;
    }

    public function setAppVersion(?string $appVersion)
    {
        $this->appVersion = $appVersion;
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

    public function getLastUsedAt()
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTime $lastUsedAt)
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isRevoked()
    {
        return $this->status === 'REVOKED';
    }
}
