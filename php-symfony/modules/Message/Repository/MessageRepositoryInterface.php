<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Message;

interface MessageRepositoryInterface
{
    public function save(Message $message): void;

    public function findById(string $id): ?Message;

    public function findByConversationId(string $conversationId): array;

    public function findByConversationIdWithPagination(string $conversationId, int $limit, int $offset): array;

    public function findRecentByUserId(string $userId, int $limit = 10): array;

    public function findRecent(int $limit = 10): array;

    public function countByConversationId(string $conversationId): int;

    public function countByUserId(string $userId): int;

    public function delete(string $id): void;

    public function findByThreadId(string $threadId): array;

    public function findLatestMessagesByConversationIds(array $conversationIds): array;

    public function searchMessages(string $query, ?string $conversationId = null, ?string $userId = null, int $limit = 50): array;

    public function isUserParticipant(string $conversationId, string $userId): bool;

    public function markAsReadForUserInConversation(string $userId, string $conversationId): void;

    public function countUnreadMessagesInConversation(string $conversationId, string $userId): int;

    public function countAll(): int;

    public function findMessagesPaginated(int $page, int $limit, ?string $conversationId = null): array;

    public function getMessageVolume(?string $startDate = null, ?string $endDate = null): array;

    public function getPeakHours(?string $startDate = null, ?string $endDate = null): array;

    public function findByConversationIdWithCursor(string $conversationId, ?string $cursor = null, int $limit = 50): array;

    public function findBySenderId(string $senderId): array;
}
