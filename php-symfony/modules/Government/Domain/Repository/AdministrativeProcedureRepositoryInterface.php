<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\AdministrativeProcedure;

interface AdministrativeProcedureRepositoryInterface
{
    public function save(AdministrativeProcedure $procedure): void;
    
    public function findById(string $id): ?AdministrativeProcedure;
    
    public function findByCode(string $code): ?AdministrativeProcedure;
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    
    public function count(array $filters = []): int;
    
    public function delete(string $id): bool;
}
