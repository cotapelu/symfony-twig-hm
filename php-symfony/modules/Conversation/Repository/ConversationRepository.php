<?php

namespace App\Conversation\Repository;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

class ConversationRepository implements ConversationRepositoryInterface
{
    private \PDO $pdo;
    private string $table = 'conversations';

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Conversation
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateConversation($row);
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%$userId%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateConversation'], $rows);
    }

    public function findDirectConversation(int $user1, int $user2): ?Conversation
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE type = 'direct' AND deleted_at IS NULL");
        $stmt->execute([]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $participants = json_decode($row['user_ids'], true);
            if (in_array($user1, $participants) && in_array($user2, $participants)) {
                return $this->hydrateConversation($row);
            }
        }

        return null;
    }

    public function save(Conversation $conversation): Conversation
    {
        if ($conversation->getId() === null) {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_ids, type, title, created_by, avatar, last_message_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                json_encode($conversation->getParticipantIds()),
                $conversation->getType(),
                $conversation->getTitle(),
                $conversation->getCreatedBy(),
                $conversation->getAvatar(),
                $conversation->getLastMessageId()
            ]);
            $conversation->setId((int)$this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table}
                SET user_ids = ?, type = ?, title = ?, created_by = ?, avatar = ?, last_message_id = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([
                json_encode($conversation->getParticipantIds()),
                $conversation->getType(),
                $conversation->getTitle(),
                $conversation->getCreatedBy(),
                $conversation->getAvatar(),
                $conversation->getLastMessageId(),
                $conversation->getId()
            ]);
        }

        return $conversation;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = datetime('now') WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findByParticipantIds(array $participantIds): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
        $stmt->execute([]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_filter(array_map([$this, 'hydrateConversation'], $rows), function ($conv) use ($participantIds) {
            return $conv->getParticipantIds() === $participantIds;
        });
    }

    public function findAllIds(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%$userId%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        
        return $ids;
    }

    public function findByParticipant(string $userId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(["%$userId%", $limit, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrateConversation'], $rows);
    }

    public function invalidateConversationCache(string $userId): void
    {
        // This is a no-op in the base repository since caching is handled by the decorator
    }

    public function getParticipantIds(string $conversationId): array
    {
        $stmt = $this->pdo->prepare("SELECT user_ids FROM {$this->table} WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$conversationId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return [];
        }
        
        return is_string($row['user_ids']) ? json_decode($row['user_ids'], true) : $row['user_ids'];
    }

    public function findByParticipantId(string $participantId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%{$participantId}%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrateConversation'], $rows);
    }

    public function findPublicConversations(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE type = 'public' AND deleted_at IS NULL");
        $stmt->execute([]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrateConversation'], $rows);
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%$userId%"]);
        return (int)$stmt->fetchColumn();
    }

    public function findConversationIdsByParticipant(string $participantId): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE user_ids LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%{$participantId}%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        
        return $ids;
    }

    public function findConversationsWithLastMessage(string $userId): array
    {
        // Simple implementation - could be optimized with JOIN
        $conversations = $this->findByParticipant($userId, 1, 100); // Get a reasonable limit
        return $conversations;
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL");
        $stmt->execute([]);
        return (int)$stmt->fetchColumn();
    }

    public function findConversationsPaginated(int $page, int $limit, string $type = 'all'): array
    {
        $offset = ($page - 1) * $limit;
        
        if ($type === 'all') {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE type = ? AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$type, $limit, $offset]);
        }
        
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateConversation'], $rows);
    }

    public function getConversationActivity(?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT c.id, c.updated_at as last_activity FROM {$this->table} c WHERE c.deleted_at IS NULL";
        $params = [];
        
        if ($startDate !== null) {
            $sql .= " AND c.updated_at >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate !== null) {
            $sql .= " AND c.updated_at <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY c.updated_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $rows;
    }

    public function isConversationAdmin(string $conversationId, string $userId): bool
    {
        // Simplified implementation - in a real app, this would check a separate table or field
        // For now, we'll consider the creator as admin
        $stmt = $this->pdo->prepare("SELECT created_by FROM {$this->table} WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$conversationId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return false;
        }
        
        return (int)$row['created_by'] === (int)$userId;
    }

    public function searchByTitle(string $userId, string $titleQuery, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->table}
            WHERE user_ids LIKE ?
              AND title LIKE ?
              AND deleted_at IS NULL
            ORDER BY updated_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(["%$userId%", "%$titleQuery%", $limit, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateConversation'], $rows);
    }

    private function hydrateConversation(array $row): Conversation
    {
        $participants = is_string($row['user_ids']) ? json_decode($row['user_ids'], true) : $row['user_ids'];
        $conversation = new Conversation($participants, $row['type']);
        $conversation->setId((int)$row['id']);
        $conversation->setTitle($row['title']);
        $conversation->setCreatedBy((int)$row['created_by']);
        $conversation->setAvatar($row['avatar']);
        $conversation->setLastMessageId($row['last_message_id'] ? (int)$row['last_message_id'] : null);

        return $conversation;
    }
}
