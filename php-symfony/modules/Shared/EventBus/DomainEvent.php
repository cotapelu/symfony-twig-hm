<?php

declare(strict_types=1);

namespace App\Shared\EventBus;

use DateTimeImmutable;

abstract class DomainEvent
{
    abstract public function occurredOn(): DateTimeImmutable;
    
    abstract public function eventType(): string;
    
    abstract protected function eventData(): array;
}
