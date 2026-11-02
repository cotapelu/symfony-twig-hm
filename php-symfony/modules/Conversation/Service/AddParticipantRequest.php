<?php

declare(strict_types=1);

namespace App\Conversation\Service;

class AddParticipantRequest
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $participantId,
        public readonly string $requesterId,
    ) {
    }
}
