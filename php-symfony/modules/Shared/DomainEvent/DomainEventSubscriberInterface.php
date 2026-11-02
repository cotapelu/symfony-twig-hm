<?php

declare(strict_types=1);

namespace App\Shared\DomainEvent;

interface DomainEventSubscriberInterface
{
    /**
     * @return string[] List of event types this subscriber handles
     */
    public static function subscribedTo(): array;

    public function handle(DomainEvent $event): void;
}