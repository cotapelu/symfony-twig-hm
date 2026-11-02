<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Ward;

interface WardRepositoryInterface
{
    public function findById(string $id): ?Ward;
    public function findByCode(string $code): ?Ward;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findByAssignedOfficer(string $officerId): array;
    public function countByAssignedOfficer(string $officerId): int;
    public function save(Ward $ward): void;
    public function delete(string $id): void;
    public function count(array $filters = []): int;
}
