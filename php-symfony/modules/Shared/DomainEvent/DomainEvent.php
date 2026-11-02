<?php

declare(strict_types=1);

namespace App\Shared\DomainEvent;

abstract class DomainEvent
{
    private string $eventId;
    private string $occurredOn;
    private array $metadata;

    public function __construct(array $metadata = [])
    {
        $this->eventId = uniqid('event_', true);
        $this->occurredOn = date('c');
        $this->metadata = array_merge([
            'version' => '1.0',
            'source' => static::class,
        ], $metadata);
    }

    abstract public function eventType(): string;

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): string
    {
        return $this->occurredOn;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'eventType' => $this->eventType(),
            'occurredOn' => $this->occurredOn,
            'metadata' => $this->metadata,
            'data' => $this->eventData(),
        ];
    }

    abstract protected function eventData(): array;
}