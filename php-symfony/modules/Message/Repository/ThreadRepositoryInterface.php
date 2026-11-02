<?php

declare(strict_types=1);

namespace App\Message\Repository;

interface ThreadRepositoryInterface
{
    public function findById(string $id): ?array;

    public function findByConversationId(string $conversationId): array;

    public function save(array $thread): void;

    public function delete(string $id): void;

    public function findRelatedThreads(string $threadId, int $limit = 5): array;
}
