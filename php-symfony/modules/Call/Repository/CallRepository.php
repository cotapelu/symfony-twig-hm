<?php

namespace App\Call\Repository;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepositoryInterface;
use App\User\Entity\User;

class CallRepository implements CallRepositoryInterface
{
    private \PDO $pdo;
    private string $table = 'call_records';

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?CallRecord
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateCall($row);
    }

    public function save(CallRecord $call): CallRecord
    {
        if ($call->getId() === null) {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (caller_id, receiver_id, conversation_id, type, status, duration, started_at, answered_at, ended_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $call->getCallerId(),
                $call->getReceiverId(),
                $call->getConversationId(),
                $call->getType(),
                $call->getStatus(),
                $call->getDuration(),
                $call->getStartedAt()->format('Y-m-d H:i:s'),
                $call->getAnsweredAt()?->format('Y-m-d H:i:s'),
                $call->getEndedAt()?->format('Y-m-d H:i:s')
            ]);
            $call->setId((int)$this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table}
                SET caller_id = ?, receiver_id = ?, conversation_id = ?, type = ?, status = ?, duration = ?, answered_at = ?, ended_at = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([
                $call->getCallerId(),
                $call->getReceiverId(),
                $call->getConversationId(),
                $call->getType(),
                $call->getStatus(),
                $call->getDuration(),
                $call->getAnsweredAt()?->format('Y-m-d H:i:s'),
                $call->getEndedAt()?->format('Y-m-d H:i:s'),
                $call->getId()
            ]);
        }

        return $call;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = datetime('now') WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE (caller_id = ? OR receiver_id = ?) AND deleted_at IS NULL");
        $stmt->execute([$userId, $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateCall'], $rows);
    }

    public function findMissedByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE receiver_id = ? AND status = 'missed' AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateCall'], $rows);
    }

    public function findByUserIdWithPagination(int $userId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE (caller_id = ? OR receiver_id = ?) AND deleted_at IS NULL ORDER BY started_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, $userId, $limit, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateCall'], $rows);
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE (caller_id = ? OR receiver_id = ?) AND deleted_at IS NULL");
        $stmt->execute([$userId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function findUserCalls(User $user, int $page, int $limit): array
    {
        $userId = $user->getId();
        $offset = ($page - 1) * $limit;
        return $this->findByUserIdWithPagination($userId, $limit, $offset);
    }

    public function countCallsByDate(User $user, string $date): int
    {
        $userId = $user->getId();
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE (caller_id = ? OR receiver_id = ?) AND deleted_at IS NULL AND DATE(started_at) = ?");
        $stmt->execute([$userId, $userId, $date]);
        return (int)$stmt->fetchColumn();
    }

    private function hydrateCall(array $row): CallRecord
    {
        $call = new CallRecord(
            (int)$row['caller_id'],
            (int)$row['receiver_id'],
            $row['type']
        );
        $call->setId((int)$row['id']);
        $call->setConversationId($row['conversation_id'] ? (int)$row['conversation_id'] : null);
        
        // Set status via state changes to properly set timestamps
        if ($row['status'] === 'answered') {
            $call->setAnswered();
        } elseif ($row['status'] === 'missed') {
            $call->markMissed();
        } elseif ($row['status'] === 'ended') {
            $call->end();
        }
        
        return $call;
    }
}
