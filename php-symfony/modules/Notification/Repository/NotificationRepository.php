<?php

declare(strict_types=1);

namespace App\Notification\Repository;

use App\Notification\Entity\Notification;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

class NotificationRepository implements NotificationRepositoryInterface
{
    private Connection $connection;
    private string $table = 'notifications';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id): ?Notification
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function findByUserId(string $userId, bool $unreadOnly = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId = ?";
        if ($unreadOnly) {
            $sql .= " AND isRead = 0";
        }
        $rows = $this->connection->fetchAllAssociative($sql, [$userId]);
        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByUserIdWithPagination(string $userId, bool $unreadOnly, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE userId = ?";
        if ($unreadOnly) {
            $sql .= " AND isRead = 0";
        }
        $sql .= " ORDER BY createdAt DESC LIMIT ? OFFSET ?";
        $rows = $this->connection->fetchAllAssociative($sql, [$userId, $limit, $offset]);
        return array_map([$this, 'hydrate'], $rows);
    }

    public function countByUserId(string $userId, bool $unreadOnly): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE userId = ?";
        if ($unreadOnly) {
            $sql .= " AND isRead = 0";
        }
        return (int) $this->connection->fetchOne($sql, [$userId]);
    }

    public function save(Notification $notification): Notification
    {
        if ($notification->getId() === null) {
            $this->connection->insert($this->table, [
                'userId' => $notification->getUserId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'data' => json_encode($notification->getData()),
                'isRead' => $notification->isRead() ? 1 : 0,
                'isSent' => $notification->isSent() ? 1 : 0,
                'channel' => $notification->getChannel(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'sentAt' => $notification->getSentAt()?->format('Y-m-d H:i:s'),
                'readAt' => $notification->getReadAt()?->format('Y-m-d H:i:s'),
            ]);
            $notification->setId((int) $this->connection->lastInsertId());
        } else {
            $this->connection->update($this->table, [
                'userId' => $notification->getUserId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'data' => json_encode($notification->getData()),
                'isRead' => $notification->isRead() ? 1 : 0,
                'isSent' => $notification->isSent() ? 1 : 0,
                'channel' => $notification->getChannel(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'sentAt' => $notification->getSentAt()?->format('Y-m-d H:i:s'),
                'readAt' => $notification->getReadAt()?->format('Y-m-d H:i:s'),
            ], ['id' => $notification->getId()]);
        }
        return $notification;
    }

    public function delete(int $id): bool
    {
        return $this->connection->delete($this->table, ['id' => $id]) > 0;
    }

    private function hydrate(array $row): Notification
    {
        $n = new Notification(
            (string) $row['userId'],
            $row['type'],
            $row['title'],
            $row['message'],
            $row['channel']
        );
        $n->setId((int) $row['id']);
        $n->setData(json_decode($row['data'] ?? '{}', true));
        if ((bool) ($row['isRead'] ?? false)) {
            $n->markAsRead();
        }
        if ((bool) ($row['isSent'] ?? false)) {
            $n->markAsSent();
        }

        return $n;
    }
}
