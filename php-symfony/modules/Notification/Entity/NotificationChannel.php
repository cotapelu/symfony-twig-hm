<?php

declare(strict_types=1);

namespace App\Notification\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notification_channels')]
#[ORM\HasLifecycleCallbacks]
class NotificationChannel
{
    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_SMS = 'SMS';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $channel;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $endpoint = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferences = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $user, string $channel)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->channel = $channel;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel(string $channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setEndpoint(?string $endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getPreferences()
    {
        return $this->preferences;
    }

    public function setPreferences(?array $preferences)
    {
        $this->preferences = $preferences;
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

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'channel' => $this->channel,
            'endpoint' => $this->endpoint,
            'preferences' => $this->preferences,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
