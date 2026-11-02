<?php

declare(strict_types=1);

namespace App\Notification\Entity;

use App\Attribute\Module;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JsonSerializable;

#[Module(name: 'Notification', dependencies: ['User'])]
#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
class Notification implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isSent = false;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $channel;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct(string $userId, string $type, string $title, string $message, string $channel)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->channel = $channel;
        $this->createdAt = new DateTimeImmutable();
    }

     public function getId() { return $this->id; }
     public function setId(?int $id) { $this->id = $id; return $this; }

     public function getUserId() { return $this->userId; }
     public function setUserId(string $userId) { $this->userId = $userId; return $this; }

     public function getType() { return $this->type; }
     public function setType(string $type) { $this->type = $type; return $this; }

     public function getTitle() { return $this->title; }
     public function setTitle(string $title) { $this->title = $title; return $this; }

     public function getMessage() { return $this->message; }
     public function setMessage(string $message) { $this->message = $message; return $this; }

     public function getData() { return $this->data; }
     public function setData(?array $data) { $this->data = $data; return $this; }

     public function isRead() { return $this->isRead; }
     public function setRead(bool $isRead) { $this->isRead = $isRead; return $this; }

     public function isSent() { return $this->isSent; }
     public function setSent(bool $isSent) { $this->isSent = $isSent; return $this; }

     public function getChannel() { return $this->channel; }
     public function setChannel(string $channel) { $this->channel = $channel; return $this; }

     public function getCreatedAt() { return $this->createdAt; }
     public function setCreatedAt(\DateTimeImmutable $dt) { $this->createdAt = $dt; return $this; }

     public function getSentAt() { return $this->sentAt; }
     public function setSentAt(?\DateTimeImmutable $dt) { $this->sentAt = $dt; return $this; }

     public function getReadAt() { return $this->readAt; }
     public function setReadAt(?\DateTimeImmutable $dt) { $this->readAt = $dt; return $this; }

     public function markAsRead()
     {
         $this->isRead = true;
         $this->readAt = new DateTimeImmutable();
         return $this;
     }

     public function markAsSent()
     {
         $this->isSent = true;
         $this->sentAt = new DateTimeImmutable();
         return $this;
     }

     public function jsonSerialize(): array
     {
         return [
             'id' => $this->id,
             'userId' => $this->userId,
             'type' => $this->type,
             'title' => $this->title,
             'message' => $this->message,
             'data' => $this->data,
             'isRead' => $this->isRead,
             'isSent' => $this->isSent,
             'channel' => $this->channel,
             'createdAt' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
             'sentAt' => $this->sentAt ? $this->sentAt->format('Y-m-d H:i:s') : null,
             'readAt' => $this->readAt ? $this->readAt->format('Y-m-d H:i:s') : null,
         ];
     }
}

#[Module(name: 'Notification', dependencies: ['User', 'Notification'])]
#[ORM\Entity]
#[ORM\Table(name: 'notification_preferences')]
class NotificationPreference implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $type;

    #[ORM\Column(type: 'boolean')]
    private bool $emailEnabled = true;

    #[ORM\Column(type: 'boolean')]
    private bool $pushEnabled = true;

    #[ORM\Column(type: 'boolean')]
    private bool $smsEnabled = false;

    #[ORM\Column(type: 'boolean')]
    private bool $inAppEnabled = true;

    public function __construct(string $userId, string $type)
    {
        $this->userId = $userId;
        $this->type = $type;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function isEmailEnabled() { return $this->emailEnabled; }
    public function isPushEnabled() { return $this->pushEnabled; }
    public function isSmsEnabled() { return $this->smsEnabled; }
    public function isInAppEnabled() { return $this->inAppEnabled; }

    public function setEmailEnabled(bool $enabled) { $this->emailEnabled = $enabled; return $this; }
    public function setPushEnabled(bool $enabled) { $this->pushEnabled = $enabled; return $this; }
    public function setSmsEnabled(bool $enabled) { $this->smsEnabled = $enabled; return $this; }
    public function setInAppEnabled(bool $enabled) { $this->inAppEnabled = $enabled; return $this; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'type' => $this->type,
            'emailEnabled' => $this->emailEnabled,
            'pushEnabled' => $this->pushEnabled,
            'smsEnabled' => $this->smsEnabled,
            'inAppEnabled' => $this->inAppEnabled,
        ];
    }
}
