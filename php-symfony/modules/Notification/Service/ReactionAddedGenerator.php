<?php

declare(strict_types=1);

namespace App\Notification\Service;

class ReactionAddedGenerator implements NotificationBodyGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'reaction_added' === $type;
    }

    public function generateBody(array $data): string
    {
        return 'Someone reacted to your message with '.($data['emoji'] ?? 'an emoji');
    }
}
