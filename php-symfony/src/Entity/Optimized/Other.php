<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.loyalty_programs')]
class LoyaltyProgram
{
    public const TYPE_POINTS = 'POINTS';
    public const TYPE_BADGES = 'BADGES';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rules = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    public function __construct(string $id, string $name, string $type)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getRules() { return $this->rules; }
    public function getStatus() { return $this->status; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.loyalty_accounts')]
#[ORM\Index(name: 'idx_loyalty_accounts_user', columns: ['user_id', 'program_id'], unique: true)]
class LoyaltyAccount
{
    public const TIER_BRONZE = 'BRONZE';
    public const TIER_SILVER = 'SILVER';
    public const TIER_GOLD = 'GOLD';
    public const TIER_PLATINUM = 'PLATINUM';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'program_id', type: 'string', length: 36)]
    private string $programId;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tier = self::TIER_BRONZE;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $userId, string $programId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->programId = $programId;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getProgramId() { return $this->programId; }
    public function getPoints() { return $this->points; }
    public function getTier() { return $this->tier; }
    public function getMetadata() { return $this->metadata; }

    public function addPoints(int $points) { $this->points += $points; return $this; }
    public function deductPoints(int $points) { $this->points -= $points; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.achievements')]
#[ORM\Index(name: 'idx_achievements_user', columns: ['user_id'])]
class Achievement
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'program_id', type: 'string', length: 36)]
    private string $programId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'earned_at', type: 'datetime')]
    private \DateTime $earnedAt;

    public function __construct(string $id, string $userId, string $programId, string $type, string $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->programId = $programId;
        $this->type = $type;
        $this->name = $name;
        $this->earnedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getProgramId() { return $this->programId; }
    public function getType() { return $this->type; }
    public function getName() { return $this->name; }
    public function getMetadata() { return $this->metadata; }
    public function getEarnedAt() { return $this->earnedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.notifications')]
#[ORM\Index(name: 'idx_notifications_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notifications_read', columns: ['read_at'])]
#[ORM\Index(name: 'idx_notifications_created', columns: ['created_at'])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTime $readAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $type, string $title, string $body)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->body = $body;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getBody() { return $this->body; }
    public function getData() { return $this->data; }
    public function getReadAt() { return $this->readAt; }
    public function getCreatedAt() { return $this->createdAt; }

    public function markAsRead() { $this->readAt = new \DateTime(); return $this; }
    public function isRead() { return $this->readAt !== null; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.notification_channels')]
#[ORM\Index(name: 'idx_notification_channels_user', columns: ['user_id'])]
class NotificationChannel
{
    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_SMS = 'SMS';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $channel;

    #[ORM\Column(type: 'string', length: 255)]
    private string $endpoint;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferences = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    public function __construct(string $id, string $userId, string $channel, string $endpoint)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->endpoint = $endpoint;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getChannel() { return $this->channel; }
    public function getEndpoint() { return $this->endpoint; }
    public function getPreferences() { return $this->preferences; }
    public function getStatus() { return $this->status; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.media_folders')]
#[ORM\Index(name: 'idx_media_folders_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_media_folders_parent', columns: ['parent_id'])]
class MediaFolder
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'parent_id', type: 'string', length: 36, nullable: true)]
    private ?string $parentId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getParentId() { return $this->parentId; }
    public function getName() { return $this->name; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setParentId(?string $id) { $this->parentId = $id; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.media_files')]
#[ORM\Index(name: 'idx_media_files_folder', columns: ['folder_id'])]
#[ORM\Index(name: 'idx_media_files_user', columns: ['user_id'])]
class MediaFile
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'folder_id', type: 'string', length: 36, nullable: true)]
    private ?string $folderId = null;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $filename;

    #[ORM\Column(name: 'mime_type', type: 'string', length: 100)]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    private int $size = 0;

    #[ORM\Column(type: 'string', length: 500)]
    private string $url;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $filename, string $mimeType, string $url)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->url = $url;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getFolderId() { return $this->folderId; }
    public function getUserId() { return $this->userId; }
    public function getFilename() { return $this->filename; }
    public function getMimeType() { return $this->mimeType; }
    public function getSize() { return $this->size; }
    public function getUrl() { return $this->url; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.events')]
#[ORM\Index(name: 'idx_events_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_events_type', columns: ['event_type'])]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'entity_type', type: 'string', length: 50)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: 'string', length: 36)]
    private string $entityId;

    #[ORM\Column(name: 'event_type', type: 'string', length: 50)]
    private string $eventType;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $entityType, string $entityId, string $eventType)
    {
        $this->id = $id;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->eventType = $eventType;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getEntityType() { return $this->entityType; }
    public function getEntityId() { return $this->entityId; }
    public function getEventType() { return $this->eventType; }
    public function getData() { return $this->data; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setData(?array $data) { $this->data = $data; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.poll_surveys')]
#[ORM\Index(name: 'idx_poll_surveys_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_poll_surveys_status', columns: ['status'])]
class PollSurvey
{
    public const TYPE_POLL = 'POLL';
    public const TYPE_SURVEY = 'SURVEY';
    public const TYPE_QUIZ = 'QUIZ';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CLOSED = 'CLOSED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $question;

    #[ORM\Column(type: 'json')]
    private array $options = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $type, string $question, array $options)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->question = $question;
        $this->options = $options;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getQuestion() { return $this->question; }
    public function getOptions() { return $this->options; }
    public function getData() { return $this->data; }
    public function getStatus() { return $this->status; }
    public function getExpiresAt() { return $this->expiresAt; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
}