<?php

declare(strict_types=1);

namespace App\Poll\Repository;

use App\Poll\Entity\Poll;

interface PollRepositoryInterface
{
    public function save(Poll $poll): void;
    public function findById(string $id): ?Poll;
    /** @return Poll[] */
    public function findByConversationId(string $conversationId): array;
    public function findByMessageId(string $messageId): ?Poll;
    public function delete(string $id): void;
}
