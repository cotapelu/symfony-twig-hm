<?php

declare(strict_types=1);

namespace App\Citizen\Application\Repository;

use App\Citizen\Domain\Entity\Citizen;

interface CitizenRepositoryInterface
{
    public function findById(string $id): ?Citizen;
    
    public function findByIdentityNumber(string $identityNumber): ?Citizen;
    
    public function findByFullName(string $fullName): array;
    
    public function findByPhoneNumber(string $phoneNumber): array;
    
    public function findByEmail(string $email): array;
    
    public function findByHouseholdId(string $householdId): array;
    
    public function search(string $query, array $filters = []): array;
    
    public function findBySpecialCategories(array $categories): array;
    
    public function findElderly(int $minAge = 60): array;
    
    public function findChildren(int $maxAge = 16): array;
    
    public function findAll(int $limit = 50, int $offset = 0): array;
    
    public function count(array $criteria = []): int;
    
    public function save(Citizen $citizen): void;
    
    public function delete(Citizen $citizen): void;
    
    public function getStatistics(): array;
}