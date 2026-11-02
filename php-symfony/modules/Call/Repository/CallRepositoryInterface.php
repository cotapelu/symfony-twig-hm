<?php

declare(strict_types=1);

namespace App\Call\Repository;

use App\Call\Entity\CallRecord;
use App\User\Entity\User;

interface CallRepositoryInterface
{
    public function findById(int $id): ?CallRecord;
    public function save(CallRecord $call): CallRecord;
    public function delete(int $id): bool;
    public function findByUserId(int $userId): array;
    public function findByUserIdWithPagination(int $userId, int $limit, int $offset): array;
    public function countByUserId(int $userId): int;
    public function findMissedByUserId(int $userId): array;
    public function findUserCalls(User $user, int $page, int $limit): array;
    public function countCallsByDate(User $user, string $date): int;
}
