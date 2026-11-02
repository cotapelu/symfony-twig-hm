<?php

declare(strict_types=1);

namespace App\Shared\EventBus;

use App\Shared\DomainEvent\DomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventBus
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
