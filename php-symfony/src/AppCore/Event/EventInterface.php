<?php

declare(strict_types=1);

namespace App\AppCore\Event;

interface EventInterface
{
    public function getEventName(): string;
    public function getEventData(): array;
    public function getEventTimestamp(): \DateTimeInterface;
}