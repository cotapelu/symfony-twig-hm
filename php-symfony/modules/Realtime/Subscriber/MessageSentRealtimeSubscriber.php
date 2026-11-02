<?php

declare(strict_types=1);

namespace App\Realtime\Subscriber;

use App\Message\Event\MessageSentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// Giả sử dùng Mercure
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MessageSentRealtimeSubscriber implements EventSubscriberInterface
{
    public function __construct(private HubInterface $hub)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageSentEvent::class => 'onMessageSent',
        ];
    }

    public function onMessageSent(MessageSentEvent $event): void
    {
        try {
            // Publish real-time update via Mercure
            $update = new Update(
                "conversation/{$event->conversationId}",
                json_encode([
                    'type' => 'new_message',
                    'message' => [
                        'id' => $event->messageId,
                        'conversation_id' => $event->conversationId,
                        'content' => $event->content,
                        'sender_id' => $event->senderId,
                        'created_at' => $event->createdAt?->format('c'),
                    ],
                ])
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            // Log but don't throw – Mercure failures should not break message sending
            error_log('Mercure publish failed: ' . $e->getMessage());
        }
    }
}
