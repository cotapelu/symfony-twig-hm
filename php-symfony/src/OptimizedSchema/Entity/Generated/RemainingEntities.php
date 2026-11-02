<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'social_posts')]
#[ORM\Index(name: 'idx_social_posts_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_social_posts_created_at', columns: ['created_at'])]
class SocialPost
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = 'post';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $media = [];

    #[ORM\Column(name: 'likes_count', type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(name: 'comments_count', type: 'integer')]
    private int $commentsCount = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): self { $this->content = $content; return $this; }
    public function getMedia(): array { return $this->media; }
    public function setMedia(array $media): self { $this->media = $media; return $this; }
    public function getLikesCount(): int { return $this->likesCount; }
    public function incrementLikesCount(): self { $this->likesCount++; return $this; }
    public function getCommentsCount(): int { return $this->commentsCount; }
    public function incrementCommentsCount(): self { $this->commentsCount++; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'social_interactions')]
#[ORM\UniqueConstraint(name: 'uq_social_interaction', columns: ['post_id', 'user_id', 'type'])]
#[ORM\Index(name: 'idx_social_interactions_post_id', columns: ['post_id'])]
class SocialInteraction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'post_id', type: 'uuid')]
    private string $postId;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public const TYPE_LIKE = 'LIKE';
    public const TYPE_COMMENT = 'COMMENT';

    public function __construct(string $id, string $postId, string $userId, string $type)
    {
        $this->id = $id;
        $this->postId = $postId;
        $this->userId = $userId;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getPostId(): string { return $this->postId; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): self { $this->content = $content; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(name: 'idx_notifications_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notifications_read_at', columns: ['read_at'])]
#[ORM\Index(name: 'idx_notifications_created_at', columns: ['created_at'])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $data = [];

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTime $readAt;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $type, string $title)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getTitle(): string { return $this->title; }
    public function getBody(): ?string { return $this->body; }
    public function setBody(?string $body): self { $this->body = $body; return $this; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): self { $this->data = $data; return $this; }
    public function getReadAt(): ?\DateTime { return $this->readAt; }
    public function isRead(): bool { return $this->readAt !== null; }
    public function markAsRead(): self { $this->readAt = new \DateTime(); return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'notification_channels')]
#[ORM\Index(name: 'idx_notification_channels_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notification_channels_channel', columns: ['channel'])]
class NotificationChannel
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $channel;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $endpoint;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $preferences = [];

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_SMS = 'SMS';

    public function __construct(string $id, string $userId, string $channel)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getChannel(): string { return $this->channel; }
    public function getEndpoint(): ?string { return $this->endpoint; }
    public function setEndpoint(?string $endpoint): self { $this->endpoint = $endpoint; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'media_folders')]
#[ORM\Index(name: 'idx_media_folders_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_media_folders_parent_id', columns: ['parent_id'])]
class MediaFolder
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(name: 'parent_id', type: 'uuid', nullable: true)]
    private ?string $parentId;

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

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getParentId(): ?string { return $this->parentId; }
    public function setParentId(?string $parentId): self { $this->parentId = $parentId; return $this; }
    public function getName(): string { return $this->name; }
}

#[ORM\Entity]
#[ORM\Table(name: 'media_files')]
#[ORM\Index(name: 'idx_media_files_folder_id', columns: ['folder_id'])]
#[ORM\Index(name: 'idx_media_files_user_id', columns: ['user_id'])]
class MediaFile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'folder_id', type: 'uuid', nullable: true)]
    private ?string $folderId;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $filename;

    #[ORM\Column(name: 'mime_type', type: 'string', length: 100, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $size;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $userId, string $filename)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->filename = $filename;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getFolderId(): ?string { return $this->folderId; }
    public function setFolderId(?string $folderId): self { $this->folderId = $folderId; return $this; }
    public function getUserId(): string { return $this->userId; }
    public function getFilename(): string { return $this->filename; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): self { $this->mimeType = $mimeType; return $this; }
    public function getSize(): ?int { return $this->size; }
    public function setSize(?int $size): self { $this->size = $size; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'events')]
#[ORM\Index(name: 'idx_events_entity_type', columns: ['entity_type'])]
#[ORM\Index(name: 'idx_events_entity_id', columns: ['entity_id'])]
#[ORM\Index(name: 'idx_events_event_type', columns: ['event_type'])]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'entity_type', type: 'string', length: 50)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: 'uuid')]
    private string $entityId;

    #[ORM\Column(name: 'event_type', type: 'string', length: 50)]
    private string $eventType;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $data = [];

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

    public function getId(): string { return $this->id; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): string { return $this->entityId; }
    public function getEventType(): string { return $this->eventType; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): self { $this->data = $data; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'poll_surveys')]
#[ORM\Index(name: 'idx_poll_surveys_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_poll_surveys_status', columns: ['status'])]
class PollSurvey
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid', nullable: true)]
    private ?string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $question;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $options = [];

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $data = [];

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const TYPE_POLL = 'POLL';
    public const TYPE_SURVEY = 'SURVEY';
    public const TYPE_QUIZ = 'QUIZ';

    public function __construct(string $id, string $type, string $question)
    {
        $this->id = $id;
        $this->type = $type;
        $this->question = $question;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getQuestion(): string { return $this->question; }
    public function getOptions(): array { return $this->options; }
    public function setOptions(array $options): self { $this->options = $options; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}