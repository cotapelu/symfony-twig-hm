<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\Budget;
use DateTimeInterface;

/**
 * Interface for Budget repository operations.
 */
interface BudgetRepositoryInterface
{
    public function findById(int $id): ?Budget;
    
    public function findByUserId(int $userId): array;
    
    public function findActiveByUserId(int $userId): array;
    
    public function findByCategoryId(int $categoryId): array;
    
    public function save(Budget $budget): Budget;
    
    public function delete(int $id): bool;
}
