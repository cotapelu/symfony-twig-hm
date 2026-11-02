<?php

declare(strict_types=1);

namespace App\Notification\Service;

class NewMessageGenerator implements NotificationBodyGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'new_message' === $type;
    }

    public function generateBody(array $data): string
    {
        return $data['message_preview'] ?? 'You have a new message';
    }
}
