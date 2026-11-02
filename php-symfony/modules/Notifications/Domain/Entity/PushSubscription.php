<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use App\Notifications\Infrastructure\Repository\PushSubscriptionRepository;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_notification_subscriptions')]
#[ORM\Index(name: 'idx_sub_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_sub_device_type', columns: ['device_type'])]
class PushSubscription
{
    public const DEVICE_TYPE_ANDROID = 'android';
    public const DEVICE_TYPE_IOS = 'ios';
    public const DEVICE_TYPE_WEB = 'web';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'device_token', type: 'text')]
    private string $deviceToken;

    #[ORM\Column(name: 'device_type', type: 'string', length: 20)]
    private string $deviceType;

    #[ORM\Column(name: 'device_name', type: 'string', length: 255, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $osVersion = null;

    #[ORM\Column(name: 'app_version', type: 'string', length: 50, nullable: true)]
    private ?string $appVersion = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'last_used_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $id,
        string $userId,
        string $deviceToken,
        string $deviceType,
        ?string $deviceName = null
    ) {
        // Validate device token
        $deviceToken = trim($deviceToken);
        if ($deviceToken === '') {
            throw new InvalidArgumentException('Device token is required');
        }

        // Validate and normalize device type
        $deviceType = strtolower(trim($deviceType));
        $allowedTypes = [self::DEVICE_TYPE_ANDROID, self::DEVICE_TYPE_IOS, self::DEVICE_TYPE_WEB];
        if (!in_array($deviceType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid device type');
        }

        // Sanitize device name if provided
        if ($deviceName !== null) {
            $deviceName = trim($deviceName);
            if (mb_strlen($deviceName) > 255) {
                throw new InvalidArgumentException('Device name cannot exceed 255 characters');
            }
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->deviceToken = $deviceToken;
        $this->deviceType = $deviceType;
        $this->deviceName = $deviceName;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getDeviceToken(): string
    {
        return $this->deviceToken;
    }

    public function setDeviceToken(string $token): void
    {
        $this->deviceToken = $token;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markUsed(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'deviceName' => $this->deviceName,
            'deviceType' => $this->deviceType,
            'osVersion' => $this->osVersion,
            'appVersion' => $this->appVersion,
            'isActive' => $this->isActive,
            'lastUsedAt' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
