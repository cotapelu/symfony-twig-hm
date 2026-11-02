<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\PopulationChange;
use App\Government\Domain\ValueObject\CitizenId;

interface PopulationChangeRepositoryInterface
{
    public function findById(string $id): ?PopulationChange;
    public function findByCitizenId(CitizenId $citizenId): array;
    public function findByType(string $changeType): array;
    public function findByStatus(string $status): array;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function save(PopulationChange $change): void;
    public function delete(string $id): void;
    public function count(array $filters = []): int;
}
