<?php

declare(strict_types=1);

namespace App\Utility\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $eventType)
    {
        $this->id = Uuid::uuid4()->toString();
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

    public function setEntityType(?string $entityType)
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getEventType()
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType)
    {
        $this->eventType = $eventType;
        return $this;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'eventType' => $this->eventType,
            'data' => $this->data,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
