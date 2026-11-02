<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\ProcedureApplication;

interface ProcedureApplicationRepositoryInterface
{
    public function save(ProcedureApplication $application): void;
    
    public function findById(string $id): ?ProcedureApplication;
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    
    public function count(array $filters = []): int;
    
    public function delete(string $id): bool;
}
