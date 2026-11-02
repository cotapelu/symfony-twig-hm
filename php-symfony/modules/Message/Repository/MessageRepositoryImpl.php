<?php
 
declare(strict_types=1);
 
namespace App\Message\Repository;
 
use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Entity\Message;
use App\Message\Entity\Message as MessageEntity;
use App\Shared\Infrastructure\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
 
class MessageRepositoryImpl implements MessageRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private \Symfony\Contracts\Cache\CacheInterface $cache;
 
    public function __construct(
        EntityManagerInterface $entityManager,
        \Symfony\Contracts\Cache\CacheInterface $cache,
        private ConversationRepositoryInterface $conversationRepository,
        private CacheService $cacheService,
    ) {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }
 
    public function save(Message $message): void
    {
        // For now, assume Message is Doctrine entity
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Invalidate caches - user conversations
        $this->cacheService->invalidateUserCaches($message->getSenderId());
    }
 
    public function findById(string $id): ?Message
    {
        return $this->entityManager->find(MessageEntity::class, $id);
    }

    /**
     * @return array<Message>
     */
    public function findByConversationId(string $conversationId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(MessageEntity::class, 'm')
            ->where('m.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Message>
     */
    public function findByConversationIdWithPagination(string $conversationId, int $limit, int $offset): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(MessageEntity::class, 'm')
            ->where('m.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->orderBy('m.createdAt', 'DESC') // Most recent first for pagination
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Message>
     */
    public function findRecentByUserId(string $userId, int $limit = 10): array
    {
        // OPTIMIZED: Use JOIN to avoid N+1 query (single query instead of 2)
        // Previously: 1 query to get conversation IDs + 1 query for messages = N+1
        // Now: 1 query with JOIN gets everything in single call
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(MessageEntity::class, 'm')
           ->innerJoin(\App\Conversation\Entity\ConversationParticipant::class, 'cp',
                'WITH', 'm.conversationId = cp.conversationId')
           ->innerJoin(\App\Conversation\Entity\Conversation::class, 'c',
                'WITH', 'cp.conversationId = c.id')
           ->where('cp.userId = :userId')
           ->andWhere('cp.isActive = true')
           ->andWhere('c.isArchived = false')
           ->andWhere('m.senderId != :userId') // Don't include user's own messages
           ->setParameter('userId', $userId)
           ->orderBy('m.createdAt', 'DESC')
           ->setMaxResults($limit);
 
        $messages = $qb->getQuery()->getResult();
 
        // Cache result for performance
        if (!empty($messages)) {
            $this->cache->get("messages:recent:{$userId}:{$limit}", function (ItemInterface $item) use ($messages) {
                $item->expiresAfter(300); // 5 minutes
 
                return $messages;
            });
        }
 
        return $messages;
    }

    /**
     * @return array<Message>
     */
    public function findRecent(int $limit = 10): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(MessageEntity::class, 'm')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countByConversationId(string $conversationId): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(MessageEntity::class, 'm')
           ->where('m.conversationId = :conversationId')
           ->setParameter('conversationId', $conversationId);
 
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
 
    public function countByUserId(string $userId): int
    {
        // OPTIMIZED: Use JOIN aggregation to avoid N+1 query (single query instead of 2)
        // Previously: 1 query to get conversation IDs + 1 query for each conversation = N+1
        // Now: 1 query with JOIN aggregation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(MessageEntity::class, 'm')
           ->innerJoin(\App\Conversation\Entity\ConversationParticipant::class, 'cp',
                'WITH', 'm.conversationId = cp.conversationId')
           ->innerJoin(\App\Conversation\Entity\Conversation::class, 'c',
                'WITH', 'cp.conversationId = c.id')
           ->where('cp.userId = :userId')
           ->andWhere('cp.isActive = true')
           ->andWhere('c.isArchived = false')
           ->andWhere('m.senderId = :userId')
           ->setParameter('userId', $userId);
 
        $count = (int) $qb->getQuery()->getSingleScalarResult();
 
        // Cache result
        $this->cache->get("messages:count:{$userId}", function (ItemInterface $item) use ($count) {
            $item->expiresAfter(600); // 10 minutes
            return $count;
        });
 
        return $count;
    }
 
    public function delete(string $id): void
    {
        $entity = $this->entityManager->find(MessageEntity::class, $id);
        if ($entity) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<Message>
     */
    public function findByThreadId(string $threadId): array
    {
        // For now, return empty array - threads not implemented yet
        return [];
    }

    /**
     * @return array<string, Message>
     */
    public function findLatestMessagesByConversationIds(array $conversationIds): array
    {
        if (empty($conversationIds)) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(MessageEntity::class, 'm')
            ->where('m.conversationId IN (:conversationIds)')
            ->andWhere('m.id IN (
                SELECT MAX(m2.id)
                FROM App\Message\Entity\Message m2
                WHERE m2.conversationId = m.conversationId
            )')
            ->setParameter('conversationIds', $conversationIds)
            ->orderBy('m.createdAt', 'DESC');

        $messages = $qb->getQuery()->getResult();

        $result = [];
        foreach ($messages as $message) {
            $result[$message->getConversationId()] = $message;
        }

        return $result;
    }

    /**
     * @return array<Message>
     */
    public function searchMessages(string $query, ?string $conversationId = null, ?string $userId = null, int $limit = 50): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(MessageEntity::class, 'm')
           ->orderBy('m.createdAt', 'DESC')
           ->setMaxResults($limit);

        // Full-text search using PostgreSQL tsvector
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        $qb->andWhere("TO_TSVECTOR('english', m.content) @@ PLAINTO_TSQUERY('english', :query)")
           ->setParameter('query', $escapedQuery);
  
        if ($conversationId) {
            $qb->andWhere('m.conversationId = :conversationId')
                ->setParameter('conversationId', $conversationId);
        }
  
        if ($userId) {
            // Only search in conversations where user is participant
            $qb->innerJoin(\App\Conversation\Entity\ConversationParticipant::class, 'cp',
                'WITH', 'm.conversationId = cp.conversationId')
                ->andWhere('cp.userId = :userId');
            $qb->setParameter('userId', $userId);
        }
  
        $entities = $qb->getQuery()->getResult();
  
        return $entities;
    }
 
    public function isUserParticipant(string $conversationId, string $userId): bool
    {
        // Check if conversation exists and user is participant
        $conversationIds = $this->conversationRepository->findConversationIdsByParticipant($userId);
        $isParticipant = in_array($conversationId, $conversationIds, true);

        // Cache result
        $cacheKey = "user:{$userId}:conversation:{$conversationId}:participant";
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($isParticipant) {
            $item->expiresAfter(1800);

            return $isParticipant;
        });

        return $isParticipant;
    }
 
    public function markAsReadForUserInConversation(string $userId, string $conversationId): void
    {
        // Get all unread messages for user in conversation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(MessageEntity::class, 'm')
           ->leftJoin(\App\Message\Entity\MessageReadReceipt::class, 'r',
                'WITH', 'r.messageId = m.id AND r.userId = :userId')
           ->where('m.conversationId = :conversationId')
           ->andWhere('m.senderId != :userId') // Don't mark own messages as read
           ->andWhere('r.id IS NULL') // Only unread messages
           ->setParameter('conversationId', $conversationId)
           ->setParameter('userId', $userId);
 
        $unreadMessages = $qb->getQuery()->getResult();
 
        // Create read receipts for unread messages (individual persists for now)
        foreach ($unreadMessages as $message) {
            $readReceipt = new \App\Message\Entity\MessageReadReceipt(
                $message->getId(),
                $userId
            );
            $this->entityManager->persist($readReceipt);
        }
 
        if (!empty($unreadMessages)) {
            $this->entityManager->flush();

            // Invalidate related caches
            $this->cacheService->invalidateUserCaches($userId);
        }
    }
 
    public function countUnreadMessagesInConversation(string $conversationId, string $userId): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(MessageEntity::class, 'm')
           ->leftJoin(\App\Message\Entity\MessageReadReceipt::class, 'r',
                'WITH', 'r.messageId = m.id AND r.userId = :userId')
           ->where('m.conversationId = :conversationId')
           ->andWhere('m.senderId != :userId') // Don't count own messages
           ->andWhere('r.id IS NULL') // Only unread messages
           ->setParameter('conversationId', $conversationId)
           ->setParameter('userId', $userId);
 
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
 
    public function countAll(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
            ->from(MessageEntity::class, 'm');
 
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<Message>
     */
    public function findMessagesPaginated(int $page, int $limit, ?string $conversationId = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(MessageEntity::class, 'm');

        if ($conversationId) {
            $qb->andWhere('m.conversationId = :conversationId')
                ->setParameter('conversationId', $conversationId);
        }

        $qb->orderBy('m.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->select('COUNT(m.id)');
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        $messages = $qb->getQuery()->getResult();

        return [
            'data' => $messages,
            'total' => $totalCount,
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getMessageVolume(?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DATE(m.createdAt) as date, COUNT(m.id) as count')
            ->from(MessageEntity::class, 'm')
            ->groupBy('DATE(m.createdAt)')
            ->orderBy('DATE(m.createdAt)', 'ASC');

        if ($startDate) {
            $qb->andWhere('m.createdAt >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('m.createdAt <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<mixed>
     */
    public function getPeakHours(?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('HOUR(m.createdAt) as hour, COUNT(m.id) as count')
            ->from(MessageEntity::class, 'm')
            ->groupBy('HOUR(m.createdAt)')
            ->orderBy('COUNT(m.id)', 'DESC');

        if ($startDate) {
            $qb->andWhere('m.createdAt >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('m.createdAt <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }

        return $qb->getQuery()->getResult();
    }

    public function findByConversationIdWithCursor(string $conversationId, ?string $cursor = null, int $limit = 50): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(MessageEntity::class, 'm')
            ->where('m.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->orderBy('m.createdAt', 'DESC');

        if ($cursor !== null) {
            $qb->andWhere('m.createdAt < :cursor')
                ->setParameter('cursor', new \DateTime($cursor));
        }

        $qb->setMaxResults($limit + 1);

        $messages = $qb->getQuery()->getResult();

        $hasMore = count($messages) > $limit;
        if ($hasMore) {
            array_pop($messages);
        }

        return [
            'data' => $messages,
            'next_cursor' => $hasMore && !empty($messages) ? end($messages)->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'has_more' => $hasMore,
        ];
    }

    public function isConversationAdmin(string $conversationId, string $userId): bool
    {
        return $this->conversationRepository->isConversationAdmin($conversationId, $userId);
    }

    public function findBySenderId(string $senderId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(MessageEntity::class, 'm')
            ->where('m.senderId = :senderId')
            ->setParameter('senderId', $senderId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
