<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.notifications')]
#[ORM\Index(name: 'idx_notifications_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notifications_read_at', columns: ['read_at'])]
#[ORM\Index(name: 'idx_notifications_created_at', columns: ['created_at'])]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $readAt = null;

    #[ORM\Column(type: 'datetime')]
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

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(?array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getReadAt()
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTime $readAt)
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isRead()
    {
        return $this->readAt !== null;
    }

    public function isUnread()
    {
        return $this->readAt === null;
    }

    public function markAsRead()
    {
        $this->readAt = new \DateTime();
        return $this;
    }
}
