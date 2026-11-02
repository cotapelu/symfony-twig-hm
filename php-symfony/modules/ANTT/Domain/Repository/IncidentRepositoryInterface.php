<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Repository;

use App\ANTT\Domain\Entity\Incident;

interface IncidentRepositoryInterface
{
    // Basic CRUD
    public function save(Incident $incident): void;
    public function find(int $id): ?Incident;
    public function findById(string $id): ?Incident;
    public function delete(Incident $incident): void;

    // Find by various criteria
    public function findByIncidentNumber(string $number): ?Incident;
    public function findByStatus(string $status): array;
    public function findByWard(string $wardId): array;
    public function findByWardCode(string $wardCode): array;
    public function findByOfficer(string $officerId): array;
    public function findByAssignedOfficer(string $officerId): array;
    public function findByIncidentType(string $type): array;
    public function findBySeverity(string $severity): array;
    public function findByReporterId(string $reporterId): array;
    public function findOpenIncidents(int $limit = 50, int $offset = 0): array;
    public function findUnresolved(): array;
    public function findOverdue(): array;
    public function findRecent(int $limit): array;

    // Search and count
    public function search(array $criteria): array;
    public function count(): int;
    public function countByStatus(): array;
    public function countByType(): array;
    public function countByTypeWithWard(string $wardCode): array;
    public function countByWard(string $wardId): int;
    public function countByWardCode(string $wardCode): array;
}
