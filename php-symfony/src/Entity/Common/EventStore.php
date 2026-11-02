<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.event_store')]
#[ORM\Index(name: 'idx_event_store_event_type', columns: ['event_type'])]
#[ORM\Index(name: 'idx_event_store_created_at', columns: ['created_at'])]
class EventStore
{
    use IdentifiableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $aggregateId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $aggregateType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $version = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $eventType)
    {
        $this->id = $id;
        $this->eventType = $eventType;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEventType()
    {
        return $this->eventType;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload(?array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    public function setAggregateId(?string $aggregateId)
    {
        $this->aggregateId = $aggregateId;
        return $this;
    }

    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    public function setAggregateType(?string $aggregateType)
    {
        $this->aggregateType = $aggregateType;
        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion(?int $version)
    {
        $this->version = $version;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
