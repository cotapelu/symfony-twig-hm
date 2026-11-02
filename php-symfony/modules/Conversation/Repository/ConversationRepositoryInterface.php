<?php

namespace App\Conversation\Repository;

use App\Conversation\Entity\Conversation;

interface ConversationRepositoryInterface
{
    public function findById(int $id): ?Conversation;
    public function findByUserId(int $userId): array;
    public function findDirectConversation(int $user1, int $user2): ?Conversation;
    public function save(Conversation $conversation): Conversation;
    public function delete(int $id): bool;
    public function findByParticipantIds(array $participantIds): array;
    public function findByParticipant(string $userId, int $page = 1, int $limit = 20): array;
    public function findAllIds(string $userId): array;
    public function invalidateConversationCache(string $userId): void;
    public function getParticipantIds(string $conversationId): array;
    public function findByParticipantId(string $participantId): array;
    public function findPublicConversations(): array;
    public function countByUserId(int $userId): int;
    public function findConversationIdsByParticipant(string $participantId): array;
    public function findConversationsWithLastMessage(string $userId): array;
    public function countAll(): int;
    public function findConversationsPaginated(int $page, int $limit, string $type = 'all'): array;
    public function getConversationActivity(?string $startDate = null, ?string $endDate = null): array;
    public function isConversationAdmin(string $conversationId, string $userId): bool;
    public function searchByTitle(string $userId, string $titleQuery, int $page = 1, int $limit = 20): array;
}
