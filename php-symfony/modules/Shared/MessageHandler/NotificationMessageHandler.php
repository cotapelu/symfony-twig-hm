<?php

declare(strict_types=1);

namespace App\Shared\MessageHandler;

use App\Message\NotificationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __invoke(NotificationMessage $message): void
    {
        // Simulate processing notification
        // In real implementation, send push notification, email, etc.
        error_log(sprintf(
            'Processing notification for user %s: %s (type: %s)',
            $message->userId,
            $message->message,
            $message->type
        ));

        // Add actual notification logic here
        // e.g., send to FCM, email, etc.
    }
}
