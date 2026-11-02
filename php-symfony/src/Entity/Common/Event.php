<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.events')]
#[ORM\Index(name: 'idx_events_entity_type', columns: ['entity_type'])]
#[ORM\Index(name: 'idx_events_entity_id', columns: ['entity_id'])]
#[ORM\Index(name: 'idx_events_event_type', columns: ['event_type'])]
#[ORM\Index(name: 'idx_events_created_at', columns: ['created_at'])]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $entityType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $entityId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $entityType, string $entityId, string $eventType)
    {
        $this->id = $id;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->eventType = $eventType;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function getEventType()
    {
        return $this->eventType;
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

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(?string $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
