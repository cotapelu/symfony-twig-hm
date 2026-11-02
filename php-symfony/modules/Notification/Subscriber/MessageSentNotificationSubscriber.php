<?php

declare(strict_types=1);

namespace App\Notification\Subscriber;

use App\Message\Event\MessageSentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends push notifications when messages are sent.
 * Uses data from MessageSentEvent instead of cross-module repository queries.
 *
 * @deprecated This subscriber is not yet fully implemented.
 * Requires participantIds to be added to MessageSentEvent.
 * Once event includes participant data, uncomment the implementation below.
 */
final class MessageSentNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct() {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageSentEvent::class => 'onMessageSent',
        ];
    }

    public function onMessageSent(MessageSentEvent $event): void
    {
        // Note: Participant IDs should be included in MessageSentEvent.
        // For now, this is a stub implementation.
        // TODO: Add participantIds to MessageSentEvent and uncomment below.

        // This subscriber is a stub - actual implementation requires
        // participant data to be passed in the event.
        // In a proper implementation, event would include:
        // public readonly array $participantIds

        return;

        /*
        // Remove sender from participants
        $recipients = array_filter($event->participantIds, fn ($id) => $id !== $event->senderId);

        // Get sender info for notification
        $sender = $this->userRepository->findById($event->senderId);
        $senderName = $sender ? $sender->getDisplayName() : 'Unknown User';

        // Create notification data
        $notificationData = $this->pushNotificationService->createMessageNotificationData(
            senderName: $senderName,
            messagePreview: $this->truncateMessage($event->content),
            conversationId: $event->conversationId,
            messageId: $event->messageId
        );

        // Send to each recipient
        foreach ($recipients as $userId) {
            // Get active device tokens for user
            $deviceTokens = $this->entityManager->getRepository(\App\User\Entity\DeviceToken::class)
                ->findBy(['userId' => $userId, 'isActive' => true]);

            foreach ($deviceTokens as $deviceToken) {
                try {
                    $this->pushNotificationService->sendNotification(
                        $deviceToken->getToken(),
                        $notificationData['title'],
                        $notificationData['body'],
                        $notificationData['data']
                    );
                } catch (\Exception $e) {
                    error_log("Failed to send push notification to device {$deviceToken->getId()}: ".$e->getMessage());
                }
            }
        }
        */
    }

    private function truncateMessage(string $content, int $maxLength = 100): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength - 3).'...';
    }
}
