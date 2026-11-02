<?php

declare(strict_types=1);

namespace App\User\EventSubscriber;

use App\Shared\DomainEvent\DomainEventSubscriberInterface;
use App\Shared\DomainEvent\DomainEvent;
use App\User\DomainEvent\UserRegistered;
use App\User\DomainEvent\UserPresenceChanged;

class UserAnalyticsSubscriber implements DomainEventSubscriberInterface
{
    public static function subscribedTo(): array
    {
        return [
            'user.registered',
            'user.presence.changed',
        ];
    }

    public function handle(DomainEvent $event): void
    {
        if ($event instanceof UserRegistered) {
            // Track user registration for analytics
            error_log(sprintf(
                '[ANALYTICS] New user registered: %s (%s)',
                $event->email(),
                $event->userId()
            ));

            // Could send welcome email, create default settings, etc.
        }

        if ($event instanceof UserPresenceChanged) {
            // Track user activity patterns
            error_log(sprintf(
                '[ANALYTICS] User %s status changed to: %s',
                $event->userId(),
                $event->status()
            ));
        }
    }
}