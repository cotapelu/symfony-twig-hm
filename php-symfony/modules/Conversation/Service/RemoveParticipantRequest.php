<?php

declare(strict_types=1);

namespace App\Conversation\Service;

class RemoveParticipantRequest
{
    public function __construct(
        public readonly string $conversationId,
        public readonly string $participantId,
        public readonly string $requesterId,
    ) {
    }
}
