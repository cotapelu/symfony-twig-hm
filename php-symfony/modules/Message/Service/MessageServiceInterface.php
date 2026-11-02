<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;

interface MessageServiceInterface
{
    public function sendMessage(array $data, string $userId): Message;
    public function getConversationMessages(string $conversationId, string $userId, int $page = 1, int $limit = 50): array;
    public function getConversationMessagesCursor(string $conversationId, string $userId, ?string $cursor = null, int $limit = 50): array;
}
