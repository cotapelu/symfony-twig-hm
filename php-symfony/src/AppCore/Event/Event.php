<?php

namespace App\AppCore\Event;

abstract class Event
{
    private string $name;
    private array $data;
    private \DateTimeImmutable $timestamp;
    private ?string $correlationId;
    private ?string $causationId;

    public function __construct(string $name, array $data = [], ?string $correlationId = null, ?string $causationId = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->timestamp = new \DateTimeImmutable();
        $this->correlationId = $correlationId ?? $this->generateId();
        $this->causationId = $causationId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function generateId(): string
    {
        return uniqid('event_', true);
    }
}

class DomainEvent extends Event
{
    public function __construct(string $name, array $data = [])
    {
        parent::__construct($name, $data);
    }

    public function toPayload(): array
    {
        return [
            'event_type' => $this->getName(),
            'data' => $this->getData(),
            'timestamp' => $this->getTimestamp()->format(\DateTime::ATOM),
            'correlation_id' => $this->getCorrelationId(),
            'event_version' => '1.0',
        ];
    }
}

class IntegrationEvent extends Event
{
    private string $aggregateId;
    private int $aggregateVersion;

    public function __construct(string $name, array $data, string $aggregateId, int $aggregateVersion = 1)
    {
        parent::__construct($name, $data);
        $this->aggregateId = $aggregateId;
        $this->aggregateVersion = $aggregateVersion;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function toPayload(): array
    {
        return [
            'event_type' => $this->getName(),
            'data' => $this->getData(),
            'aggregate_id' => $this->aggregateId,
            'aggregate_version' => $this->aggregateVersion,
            'timestamp' => $this->getTimestamp()->format(\DateTime::ATOM),
            'correlation_id' => $this->getCorrelationId(),
            'event_version' => '1.0',
        ];
    }
}

class EventListener
{
    private $listener;
    private int $priority;

    public function __construct(callable $listener, int $priority = 0)
    {
        $this->listener = $listener;
        $this->priority = $priority;
    }

    public function getListener(): callable
    {
        return $this->listener;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function __invoke(Event $event): void
    {
        call_user_func($this->listener, $event);
    }
}