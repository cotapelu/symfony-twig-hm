<?php

declare(strict_types=1);

namespace App\Shared\DomainEvent;

interface DomainEventPublisherInterface
{
    public function publish(DomainEvent $event): void;

    /**
     * @param DomainEvent[] $events
     */
    public function publishMultiple(array $events): void;
}