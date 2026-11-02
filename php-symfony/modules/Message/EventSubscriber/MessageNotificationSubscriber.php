<?php

declare(strict_types=1);

namespace App\Message\EventSubscriber;

use App\Message\DomainEvent\MessageSent;
use App\Shared\DomainEvent\DomainEventSubscriberInterface;
use App\Shared\DomainEvent\DomainEvent;

class MessageNotificationSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
    ) {
    }

    public static function subscribedTo(): array
    {
        return [
            'message.sent',
        ];
    }

    public function handle(DomainEvent $event): void
    {
        if ($event instanceof MessageSent) {
            // Notify other participants in the conversation
            // This would trigger push notifications, email alerts, etc.

            // For now, just log the event
            error_log(sprintf(
                '[MESSAGE] Message %s sent in conversation %s by user %s',
                $event->messageId(),
                $event->conversationId(),
                $event->senderId()
            ));

            // TODO: Implement actual notification logic
            // $this->notificationService->notifyConversationParticipants(
            //     $event->conversationId(),
            //     $event->senderId(),
            //     'New message: ' . substr($event->content(), 0, 50) . '...'
            // );
        }
    }
}