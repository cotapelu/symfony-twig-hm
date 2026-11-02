<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use App\OptimizedSchema\Entity\Trait\StatusAwareTrait;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity]
#[ORM\Table(name: 'common.notification_channels')]
#[ORM\Index(name: 'idx_notification_channels_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notification_channels_channel', columns: ['channel'])]
#[ORM\UniqueConstraint(name: 'uq_notification_channel_user_channel', columns: ['user_id', 'channel'])]
class NotificationChannel
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use StatusAwareTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $channel;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $endpoint = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferences = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    public function __construct(string $id, string $userId, string $channel)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->channel = $channel;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getChannel()
    {
        return $this->channel;
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

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isDisabled()
    {
        return $this->status === 'DISABLED';
    }

    public function isEnabled()
    {
        return $this->isActive();
    }
}
