<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Repository;

use App\ANTT\Domain\Entity\Patrol;

interface PatrolRepositoryInterface
{
    // Basic CRUD
    public function save(Patrol $patrol): void;
    public function find(int $id): ?Patrol;
    public function findById(string $id): ?Patrol;
    public function delete(Patrol $patrol): void;

    // Find by various criteria
    public function findByPatrolNumber(string $number): ?Patrol;
    public function findByTeamId(string $teamId): array;
    public function findByStatus(string $status): array;
    public function findByDate(\DateTimeImmutable $date): array;
    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
    public function findByWard(string $wardId): array;
    public function findByWardCode(string $wardCode): array;
    public function findByOfficer(string $officerId): array;
    public function findUpcoming(int $limit): array;
    public function findScheduled(): array;
    public function findInProgress(): array;
    public function findRecent(int $limit): array;

    // Search and count
    public function search(array $criteria): array;
    public function count(): int;
    public function countByStatus(): array;
    public function countByTeam(): array;
    public function countByOfficer(string $officerId): int;
    public function countByWard(string $wardId): int;
}
