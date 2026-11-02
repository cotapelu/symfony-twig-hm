<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\ProcedureRequest;

interface ProcedureRequestRepositoryInterface
{
    public function findById(string $id): ?ProcedureRequest;
    public function findByCitizen(string $citizenId, array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findByOfficer(string $officerId, array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findByProcedure(string $procedureId, array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findOverdue(): array;
    public function findDueWithinDays(int $days): array;
    public function save(ProcedureRequest $request): void;
    public function delete(string $id): void;
    public function count(array $filters = []): int;
    public function countOverdue(): int;
    public function countByStatus(string $status): int;
}
