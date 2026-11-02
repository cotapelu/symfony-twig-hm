<?php

declare(strict_types=1);

namespace App\Conversation\Repository;

use App\Conversation\Entity\Conversation;
use App\Shared\Infrastructure\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Interface for conversation repository with caching support.
 */
interface ConversationRepositoryWithCache extends ConversationRepositoryInterface
{
    public function getConversationCount(int $userId): int;
    public function incrementConversationCount(int $userId): void;
}

class ConversationCacheDecorator implements ConversationRepositoryWithCache
{
    private ConversationRepositoryInterface $decoratedRepository;
    private CacheInterface $cache;
    private CacheService $cacheService;

    public function __construct(
        ConversationRepositoryInterface $decoratedRepository,
        CacheInterface $cache,
        CacheService $cacheService
    ) {
        $this->decoratedRepository = $decoratedRepository;
        $this->cache = $cache;
        $this->cacheService = $cacheService;
    }

    public function findById(int $id): ?Conversation
    {
        return $this->decoratedRepository->findById($id);
    }

    public function findByParticipant(string $userId, int $page = 1, int $limit = 20): array
    {
        return $this->decoratedRepository->findByParticipant($userId, $page, $limit);
    }

    public function getConversationCount(int $userId): int
    {
        // Try to get from cache first using CacheService method
        $cachedCount = $this->cacheService->getConversationMessageCount((string) $userId);
        if ($cachedCount !== null) {
            return (int) $cachedCount;
        }
        
        // If not in cache, get count from decorated repository
        $count = $this->decoratedRepository->countByUserId($userId);
        
        // Note: CacheService doesn't have a setter for conversation count
        // We would need to add such a method to CacheService to cache the value
        // For now, we'll return the count without caching to avoid breaking changes
        return $count;
    }

    public function findAllIds(string $userId): array
    {
        // Since the decorated repository uses PDO directly, we need to use PDO as well
        $stmt = $this->decoratedRepository->pdo->prepare("SELECT id FROM {$this->decoratedRepository->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%$userId%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        
        return $ids;
    }

    public function incrementConversationCount(int $userId): void
    {
        $this->cache->delete(sprintf("conversation:count:%s", $userId));
    }

    public function invalidateConversationCache(string $userId): void
    {
        $this->cache->delete(sprintf("conversation:count:%s", $userId));
        $this->cache->delete("conversations:all:ids");
    }

    public function save(Conversation $conversation): Conversation
    {
        return $this->decoratedRepository->save($conversation);
    }

    public function getParticipantIds(string $conversationId): array
    {
        // Note: This method uses Doctrine QueryBuilder, but the decorated repository uses PDO
        // We need to handle this appropriately
        // For now, we'll fall back to the decorated repository's implementation
        return $this->decoratedRepository->getParticipantIds($conversationId);
    }

    // Required interface methods delegated to decorated repository
     
    public function findByUserId(int $userId): array
    {
        return $this->decoratedRepository->findByUserId($userId);
    }

    public function findDirectConversation(int $user1, int $user2): ?Conversation
    {
        return $this->decoratedRepository->findDirectConversation($user1, $user2);
    }

    public function findByParticipantIds(array $participantIds): array
    {
        return $this->decoratedRepository->findByParticipantIds($participantIds);
    }

    public function findByParticipantId(string $participantId): array
    {
        return $this->decoratedRepository->findByParticipantId($participantId);
    }

    public function findPublicConversations(): array
    {
        return $this->decoratedRepository->findPublicConversations();
    }

    public function delete(int $id): bool
    {
        return $this->decoratedRepository->delete($id);
    }

    public function countByUserId(int $userId): int
    {
        return $this->decoratedRepository->countByUserId($userId);
    }

    public function findConversationIdsByParticipant(string $participantId): array
    {
        return $this->decoratedRepository->findConversationIdsByParticipant($participantId);
    }

    public function findConversationsWithLastMessage(string $userId): array
    {
        return $this->decoratedRepository->findConversationsWithLastMessage($userId);
    }

    public function countAll(): int
    {
        return $this->decoratedRepository->countAll();
    }

    public function findConversationsPaginated(int $page, int $limit, string $type = 'all'): array
    {
        return $this->decoratedRepository->findConversationsPaginated($page, $limit, $type);
    }

    public function getConversationActivity(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->decoratedRepository->getConversationActivity($startDate, $endDate);
    }

    public function isConversationAdmin(string $conversationId, string $userId): bool
    {
        return $this->decoratedRepository->isConversationAdmin($conversationId, $userId);
    }

    public function searchByTitle(string $userId, string $titleQuery, int $page = 1, int $limit = 20): array
    {
        return $this->decoratedRepository->searchByTitle($userId, $titleQuery, $page, $limit);
    }
}