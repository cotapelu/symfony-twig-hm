<?php

namespace App\Notification\Repository;

use App\Notification\Entity\Notification;

interface NotificationRepositoryInterface
{
    public function findById(int $id): ?Notification;
    public function findByUserId(string $userId, bool $unreadOnly = false): array;
    public function findByUserIdWithPagination(string $userId, bool $unreadOnly, int $limit, int $offset): array;
    public function countByUserId(string $userId, bool $unreadOnly): int;
    public function save(Notification $notification): Notification;
    public function delete(int $id): bool;
}
