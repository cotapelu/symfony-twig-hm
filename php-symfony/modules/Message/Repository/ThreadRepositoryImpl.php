<?php

declare(strict_types=1);

namespace App\Message\Repository;

class ThreadRepositoryImpl implements ThreadRepositoryInterface
{
    // In-memory storage for now - would use database in production
    private array $threads = [];

    public function findById(string $id): ?array
    {
        return $this->threads[$id] ?? null;
    }

    public function findByConversationId(string $conversationId): array
    {
        return array_filter($this->threads, function ($thread) use ($conversationId) {
            return $thread['conversationId'] === $conversationId;
        });
    }

    public function save(array $thread): void
    {
        $this->threads[$thread['id']] = $thread;
    }

    public function delete(string $id): void
    {
        unset($this->threads[$id]);
    }

    public function findRelatedThreads(string $threadId, int $limit = 5): array
    {
        if (!isset($this->threads[$threadId])) {
            return [];
        }

        $currentThread = $this->threads[$threadId];
        $conversationId = $currentThread['conversationId'];

        return array_slice(
            array_filter($this->threads, function ($thread) use ($conversationId, $threadId) {
                return $thread['conversationId'] === $conversationId && $thread['id'] !== $threadId;
            }),
            0,
            $limit
        );
    }
}
