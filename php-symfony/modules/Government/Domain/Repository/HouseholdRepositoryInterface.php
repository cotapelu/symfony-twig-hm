<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Household;
use App\Government\Domain\ValueObject\HouseholdId;

interface HouseholdRepositoryInterface
{
    public function findById(HouseholdId $id): ?Household;
    public function findByNumber(string $householdNumber): ?Household;
    public function findByHeadOfHousehold(string $citizenId): ?Household;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findActive(): array;
    public function findDissolved(): array;
    public function save(Household $household): void;
    public function delete(HouseholdId $id): void;
    public function count(array $filters = []): int;
}
