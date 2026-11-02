<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class NotificationStatsService
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    private function getReadOnlyEm()
    {
        return $this->registry->getManager('read_only');
    }

    /**
     * Returns per-type notification counts for a user.
     * Heavy aggregation runs on read replica.
     */
    public function getUserNotificationStats(int $userId)
    {
        $conn = $this->getReadOnlyEm()->getConnection();
        $sql = 'SELECT type, COUNT(*) as count, MAX(created_at) as last_at FROM notifications WHERE user_id = ? GROUP BY type';
        $stmt = $conn->executeQuery($sql, [$userId]);
        return $stmt->fetchAllAssociative();
    }
}
