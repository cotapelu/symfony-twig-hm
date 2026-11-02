<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\ValueObject\CitizenId;
use App\Government\Domain\ValueObject\Address;

interface CitizenRepositoryInterface
{
    public function findById(CitizenId $id): ?Citizen;
    public function findByIdentityNumber(string $identityNumber): ?Citizen;
    public function findByEmail(string $email): ?Citizen;
    public function findByPhone(string $phone): ?Citizen;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function findByAddress(Address $address): array;
    public function findByHousehold(string $householdId): array;
    public function findByAgeRange(int $minAge, int $maxAge): array;
    public function getCitizensByProvince(): array;
    public function getCitizensByAgeRange(): array;
    public function findDeceased(): array;
    public function findActive(): array;
    public function search(string $query): array;
    public function save(Citizen $citizen): void;
    public function delete(CitizenId $id): void;
    public function count(array $filters = []): int;
}
