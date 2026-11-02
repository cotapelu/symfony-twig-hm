<?php

declare(strict_types=1);

namespace App\SampleModule\Domain\Event;

/**
 * Sample Domain Event
 * Represents something that happened in the domain
 */
class SampleEvent
{
    private int $entityId;
    private string $name;
    private int $value;
    private \DateTimeImmutable $occurredOn;

    public function __construct(int $entityId, string $name, int $value)
    {
        $this->entityId = $entityId;
        $this->name = $name;
        $this->value = $value;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}