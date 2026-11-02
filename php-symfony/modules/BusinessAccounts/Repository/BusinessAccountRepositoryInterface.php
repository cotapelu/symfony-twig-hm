<?php

namespace App\BusinessAccounts\Repository;

use App\BusinessAccounts\Entity\BusinessAccount;

interface BusinessAccountRepositoryInterface
{
    public function findById(int $id): ?BusinessAccount;
    public function findByUserId(string $userId, int $limit = 20, int $offset = 0): array;
    public function save(BusinessAccount $account): BusinessAccount;
    public function delete(int $id): bool;

    // Search
    public function searchByUserIdAndName(int $userId, string $name, int $limit = 20, int $offset = 0): array;

    public function countByUser(string $userId): int;

    // Additional methods for skipped tests
    public function findVerified(): array;
    public function searchByName(string $name): array;
    public function findByType(string $type): array;
    public function findByTaxId(string $taxId): ?BusinessAccount;
}
