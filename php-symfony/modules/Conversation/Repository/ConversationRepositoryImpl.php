<?php

declare(strict_types=1);

namespace App\Conversation\Repository;

use App\Conversation\Entity\Conversation;
use App\Shared\Infrastructure\CacheService;
use Doctrine\ORM\EntityManagerInterface;

class ConversationRepositoryImpl implements ConversationRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private CacheService $cacheService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheService $cacheService,
    ) {
        $this->entityManager = $entityManager;
        $this->cacheService = $cacheService;
    }

    public function save(Conversation $conversation): Conversation
    {
        $this->entityManager->beginTransaction();
        try {
            // Assume Conversation is Doctrine entity
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        // Invalidate caches for all participants
        foreach ($conversation->getParticipantIds() as $userId) {
            $this->cacheService->invalidateConversationList((string) $userId);
        }
        $conversationId = $conversation->getId();
        if ($conversationId !== null) {
            $this->cacheService->invalidateConversationCaches((string) $conversationId);
        }
        
        return $conversation;
    }

    public function findById(int $id): ?Conversation
    {
        return $this->entityManager->find(Conversation::class, $id);
    }

    /** @return Conversation[] */
    public function findByUserId(int $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c', 'p')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :userId')
            ->andWhere('p.isActive = true')
            ->andWhere('c.isArchived = false')
            ->setParameter('userId', $userId)
            ->orderBy('c.updatedAt', 'DESC');

        $entities = $qb->getQuery()->getResult();

        return $entities;
    }

    /** @return Conversation[] */
    public function findByParticipantId(string $participantId): array
    {
        return $this->findByUserId((int) $participantId);
    }

    /** @return Conversation[] */
    public function findPublicConversations(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c')
            ->where('c.isPublic = true')
            ->andWhere('c.isArchived = false')
            ->orderBy('c.createdAt', 'DESC');

        $entities = $qb->getQuery()->getResult();

        return $entities;
    }

    public function delete(int $id): bool
    {
        $entity = $this->entityManager->find(Conversation::class, $id);
        if ($entity) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function countByUserId(int $userId): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT c.id)')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('p.isActive = true')
            ->andWhere('c.isArchived = false');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return string[] */
    public function findConversationIdsByParticipant(string $participantId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c.id')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :participantId')
            ->setParameter('participantId', $participantId)
            ->andWhere('p.isActive = true')
            ->andWhere('c.isArchived = false');

        $result = $qb->getQuery()->getScalarResult();
        return array_column($result, 'id');
    }

    /** @return array<array> */
    public function findConversationsWithLastMessage(string $userId): array
    {
        // Use a single query with LEFT JOIN to get conversations and their last message
        $sql = '
            SELECT
                c.id,
                c.title,
                c.type,
                c.creator_id,
                c.created_at,
                c.updated_at,
                c.is_active,
                c.is_archived,
                c.is_public,
                c.description,
                c.avatar_url,
                lm.id as last_message_id,
                lm.content as last_message_content,
                lm.sender_id as last_message_sender_id,
                lm.type as last_message_type,
                lm.created_at as last_message_created_at
            FROM communication_conversations c
            LEFT JOIN (
                SELECT m1.*
                FROM communication_messages m1
                WHERE m1.created_at = (
                    SELECT MAX(m2.created_at)
                    FROM communication_messages m2
                    WHERE m2.conversation_id = m1.conversation_id
                )
            ) lm ON c.id = lm.conversation_id
            INNER JOIN communication_conversation_participants cp ON c.id = cp.conversation_id
            WHERE cp.user_id = :userId AND cp.is_active = 1
            AND c.is_archived = 0
            ORDER BY COALESCE(lm.created_at, c.created_at) DESC
        ';

        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        $results = $result->fetchAllAssociative();

        // Convert to array format expected by controller
        $conversations = [];
        foreach ($results as $row) {
            $conversations[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'type' => $row['type'],

                'creator_id' => $row['creator_id'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'is_active' => (bool) $row['is_active'],
                'is_archived' => (bool) $row['is_archived'],
                'is_public' => (bool) $row['is_public'],
                'description' => $row['description'],
                'avatar_url' => $row['avatar_url'],
                'last_message' => $row['last_message_id'] ? [
                    'id' => $row['last_message_id'],
                    'content' => $row['last_message_content'],
                    'sender_id' => $row['last_message_sender_id'],
                    'type' => $row['last_message_type'],
                    'created_at' => $row['last_message_created_at'],
                ] : null,
            ];
        }

        return $conversations;
    }

    public function countAll(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT c.id)')
            ->from(Conversation::class, 'c');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findConversationsPaginated(int $page, int $limit, string $type = 'all'): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c');

        if ($type === 'direct') {
            $qb->andWhere('c.type = :type')->setParameter('type', 'direct');
        } elseif ($type === 'group') {
            $qb->andWhere('c.type = :type')->setParameter('type', 'group');
        }

        $qb->orderBy('c.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT c.id)');
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        $conversations = $qb->getQuery()->getResult();

        return [
            'data' => $conversations,
            'total' => $totalCount,
        ];
    }

    public function getConversationActivity(?string $startDate = null, ?string $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DATE(c.createdAt) as date, COUNT(c.id) as count')
            ->from(Conversation::class, 'c')
            ->groupBy('DATE(c.createdAt)')
            ->orderBy('DATE(c.createdAt)', 'ASC');

        if ($startDate) {
            $qb->andWhere('c.createdAt >= :startDate')->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('c.createdAt <= :endDate')->setParameter('endDate', new \DateTime($endDate));
        }

        return $qb->getQuery()->getResult();
    }

    public function isConversationAdmin(string $conversationId, string $userId): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(cp.id)')
            ->from(\App\Conversation\Entity\ConversationParticipant::class, 'cp')
            ->where('cp.conversationId = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->andWhere('cp.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('cp.role = :role')
            ->setParameter('role', 'admin');

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        return $count > 0;
    }

    public function findDirectConversation(int $user1, int $user2): ?Conversation
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c')
            ->where('c.type = :type')
            ->setParameter('type', 'direct')
            ->andWhere('c.isArchived = false');

        $conversations = $qb->getQuery()->getResult();

        foreach ($conversations as $conversation) {
            if ($conversation->hasParticipant($user1) && $conversation->hasParticipant($user2)) {
                return $conversation;
            }
        }

        return null;
    }

    public function findByParticipantIds(array $participantIds): array
    {
        // Sort participant IDs for consistent comparison
        sort($participantIds);
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c')
            ->where('c.isArchived = false');

        $conversations = $qb->getQuery()->getResult();

        $result = [];
        foreach ($conversations as $conversation) {
            $convParticipantIds = $conversation->getParticipantIds();
            sort($convParticipantIds);
            
            if ($convParticipantIds === $participantIds) {
                $result[] = $conversation;
            }
        }

        return $result;
    }

    public function findByParticipant(string $userId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('p.isActive = true')
            ->andWhere('c.isArchived = false')
            ->orderBy('c.updatedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function invalidateConversationCache(string $userId): void
    {
        // This is a no-op in the base repository since caching is handled by the decorator
        // The decorator handles cache invalidation
    }

    public function getParticipantIds(string $conversationId): array
    {
        $conversation = $this->entityManager->find(Conversation::class, $conversationId);
        if (!$conversation) {
            return [];
        }
        
        return $conversation->getParticipantIds();
    }

    public function findAllIds(string $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c.id')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('p.isActive = true')
            ->andWhere('c.isArchived = false');

        $result = $qb->getQuery()->getScalarResult();
        return array_column($result, 'id');
    }

    public function searchByTitle(string $userId, string $titleQuery, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Conversation::class, 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('c.title LIKE :query')
            ->setParameter('query', '%' . $titleQuery . '%')
            ->andWhere('c.isArchived = false')
            ->orderBy('c.updatedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}