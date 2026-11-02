<?php

declare(strict_types=1);

namespace App\ANTT\Domain\Repository;

use App\ANTT\Domain\Entity\SecurityCase;

interface SecurityCaseRepositoryInterface
{
    // Basic CRUD
    public function save(SecurityCase $case): void;
    public function find(int $id): ?SecurityCase;
    public function findById(string $id): ?SecurityCase;
    public function delete(SecurityCase $case): void;

    // Find by various criteria
    public function findByCaseNumber(string $number): ?SecurityCase;
    public function findByStatus(string $status): array;
    public function findByPriority(string $priority): array;
    public function findByCaseType(string $type): array;
    public function findByInvestigationOfficer(string $officerId): array;
    public function findByOfficer(string $officerId): array;
    public function findByWard(string $wardId): array;
    public function findByWardCode(string $wardCode): array;
    public function findOpenCases(): array;
    public function findOpen(): array;
    public function findUnresolved(): array;
    public function findRecent(int $limit): array;

    // Search and count
    public function search(array $criteria): array;
    public function count(): int;
    public function countByStatus(): array;
    public function countByType(): array;
    public function countByPriority(): array;
}
