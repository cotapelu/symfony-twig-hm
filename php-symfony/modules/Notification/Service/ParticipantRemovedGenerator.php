<?php

declare(strict_types=1);

namespace App\Notification\Service;

class ParticipantRemovedGenerator implements NotificationBodyGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'participant_removed' === $type;
    }

    public function generateBody(array $data): string
    {
        return 'You were removed from a conversation';
    }
}
