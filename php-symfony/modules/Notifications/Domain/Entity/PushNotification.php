<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use App\Notifications\Infrastructure\Repository\PushNotificationRepository;

#[ORM\Entity(repositoryClass: PushNotificationRepository::class)]
#[ORM\Table(name: 'push_notifications')]
#[ORM\Index(name: 'idx_notification_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notification_type', columns: ['notification_type'])]
#[ORM\Index(name: 'idx_notification_status', columns: ['status'])]
class PushNotification
{
    public const TYPE_QUEUE_CALLED = 'queue_called';
    public const TYPE_QUEUE_COMPLETED = 'queue_completed';
    public const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';
    public const TYPE_APPOINTMENT_CONFIRMED = 'appointment_confirmed';
    public const TYPE_FEEDBACK_RESPONSE = 'feedback_response';
    public const TYPE_DOCUMENT_STATUS = 'document_status';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CLICKED = 'clicked';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'notification_type', type: 'string', length: 50)]
    private string $notificationType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'action_url', type: 'string', length: 500, nullable: true)]
    private ?string $actionUrl = null;

    #[ORM\Column(name: 'action_label', type: 'string', length: 100, nullable: true)]
    private ?string $actionLabel = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'priority', type: 'string', length: 20, options: ['default' => 'normal'])]
    private string $priority = 'normal';

    #[ORM\Column(name: 'scheduled_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'delivered_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(name: 'failed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $failedAt = null;

    #[ORM\Column(name: 'error_message', type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'clicked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $clickedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $retryCount = null;

    #[ORM\Column(name: 'max_retries', type: 'integer', options: ['default' => 3])]
    private int $maxRetries = 3;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $userId,
        string $notificationType,
        string $title,
        string $body,
        ?array $data = null,
        ?DateTimeImmutable $scheduledAt = null
    ) {
        $this->validateNotificationType($notificationType);
        $this->validateTitle($title);
        $this->validateBody($body);
        $this->validateData($data);
        $this->validateScheduledAt($scheduledAt);

        $this->populate($id, $userId, $notificationType, $title, $body, $data, $scheduledAt);
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    private function populate(
        string $id,
        string $userId,
        string $notificationType,
        string $title,
        string $body,
        ?array $data,
        ?DateTimeImmutable $scheduledAt
    ): void {
        $this->id = $id;
        $this->userId = $userId;
        $this->notificationType = $notificationType;
        $this->title = trim($title);
        $this->body = trim($body);
        $this->data = $data;
        $this->scheduledAt = $scheduledAt;
    }

    private function validateNotificationType(string $type): void
    {
        $allowedTypes = [
            self::TYPE_QUEUE_CALLED,
            self::TYPE_QUEUE_COMPLETED,
            self::TYPE_APPOINTMENT_REMINDER,
            self::TYPE_APPOINTMENT_CONFIRMED,
            self::TYPE_FEEDBACK_RESPONSE,
            self::TYPE_DOCUMENT_STATUS,
            self::TYPE_ANNOUNCEMENT,
        ];
        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid notification type');
        }
    }

    private function validateTitle(string $title): void
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 255) {
            throw new InvalidArgumentException('Title must be 1-255 characters');
        }
    }

    private function validateBody(string $body): void
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            throw new InvalidArgumentException('Body must be 1-2000 characters');
        }
    }

    private function validateData(?array $data): void
    {
        if ($data === null) {
            return;
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array');
        }
        if (count($data) > 10) {
            throw new InvalidArgumentException('Data payload cannot exceed 10 keys');
        }
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 10240) {
            throw new InvalidArgumentException('Data payload cannot exceed 10KB');
        }
    }

    private function validateScheduledAt(?DateTimeImmutable $scheduledAt): void
    {
        if ($scheduledAt !== null) {
            $now = new DateTimeImmutable();
            if ($scheduledAt < $now) {
                throw new InvalidArgumentException('Scheduled time must be in the future');
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getNotificationType(): string
    {
        return $this->notificationType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function setActionUrl(?string $url): void
    {
        $this->actionUrl = $url;
    }

    public function setActionLabel(?string $label): void
    {
        $this->actionLabel = $label;
    }

    public function markSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new DateTimeImmutable();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getActionLabel(): ?string
    {
        return $this->actionLabel;
    }

    public function markDelivered(): void
    {
        $this->status = self::STATUS_DELIVERED;
        $this->deliveredAt = new DateTimeImmutable();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failedAt = new DateTimeImmutable();
        $this->errorMessage = $errorMessage;
        $this->retryCount = ($this->retryCount ?? 0) + 1;
    }

    public function markClicked(): void
    {
        $this->status = self::STATUS_CLICKED;
        $this->clickedAt = new DateTimeImmutable();
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isRead(): bool
    {
        return $this->clickedAt !== null;
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && 
               ($this->retryCount ?? 0) < $this->maxRetries;
    }

    public function resetForRetry(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->errorMessage = null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'notificationType' => $this->notificationType,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'actionUrl' => $this->actionUrl,
            'actionLabel' => $this->actionLabel,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduledAt' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'sentAt' => $this->sentAt?->format('Y-m-d H:i:s'),
            'deliveredAt' => $this->deliveredAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
