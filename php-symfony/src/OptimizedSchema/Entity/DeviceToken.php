<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'device_tokens')]
#[ORM\Index(name: 'idx_device_tokens_user_id', columns: ['user_id'])]
class DeviceToken
{
    use IdentifiableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(name: 'fcm_token', type: 'string', length: 512, nullable: true)]
    private ?string $fcmToken;

    #[ORM\Column(name: 'device_type', type: 'string', length: 20, nullable: true)]
    private ?string $deviceType;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new \DateTime();
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): self
    {
        $this->fcmToken = $fcmToken;
        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): self
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}