<?php

declare(strict_types=1);

namespace App\Notification\Service;

class ParticipantAddedGenerator implements NotificationBodyGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'participant_added' === $type;
    }

    public function generateBody(array $data): string
    {
        return 'You were added to a conversation';
    }
}
