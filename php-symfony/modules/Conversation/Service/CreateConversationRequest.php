<?php

declare(strict_types=1);

namespace App\Conversation\Service;

class CreateConversationRequest
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly bool $isPublic = false,
        public readonly ?array $participantIds = null,
        public readonly ?string $userId = null,
    ) {
    }

    public function getUserId()
    {
        return $this->userId;
    }
}
