<?php

declare(strict_types=1);

namespace App\Shared\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'domain_events')]
#[ORM\Index(name: 'idx_aggregate', columns: ['aggregate_id', 'aggregate_type'])]
#[ORM\Index(name: 'idx_occurred_at', columns: ['occurred_at'])]
class StoredDomainEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'event_type', type: 'string', length: 255)]
    private string $eventType;

    #[ORM\Column(name: 'aggregate_id', type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(name: 'aggregate_type', type: 'string', length: 255)]
    private string $aggregateType;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'occurred_at', type: 'datetime')]
    private \DateTime $occurredAt;

    #[ORM\Column(name: 'version', type: 'integer')]
    private int $version;

    public function __construct(
        string $id,
        string $eventType,
        string $aggregateId,
        string $aggregateType,
        array $payload,
        int $version,
    ) {
        $this->id = $id;
        $this->eventType = $eventType;
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
        $this->payload = $payload;
        $this->occurredAt = new \DateTime();
        $this->version = $version;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEventType()
    {
        return $this->eventType;
    }

    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getOccurredAt()
    {
        return $this->occurredAt;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
