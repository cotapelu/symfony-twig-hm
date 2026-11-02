<?php

declare(strict_types=1);

namespace App\Notification\EventSubscriber;

use App\Notification\DomainEvent\NotificationSent;
use App\Shared\DomainEvent\DomainEventSubscriberInterface;
use App\Shared\DomainEvent\DomainEvent;

class NotificationLoggerSubscriber implements DomainEventSubscriberInterface
{
    public static function subscribedTo(): array
    {
        return [
            'notification.sent',
        ];
    }

    public function handle(DomainEvent $event): void
    {
        if ($event instanceof NotificationSent) {
            // Log notification for analytics/auditing
            error_log(sprintf(
                '[NOTIFICATION] %s sent to user %s via %s: %s',
                $event->type(),
                $event->userId(),
                $event->channel(),
                $event->notificationId()
            ));

            // Could also store in database for reporting
            // $this->notificationRepository->logNotification($event);
        }
    }
}