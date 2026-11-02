<?php

declare(strict_types=1);

namespace App\Shared\DomainEvent;

use App\Shared\EventBus\EventBus;

class DomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private EventBus $eventBus,
    ) {
    }

    public function publish(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }

    public function publishMultiple(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
}
