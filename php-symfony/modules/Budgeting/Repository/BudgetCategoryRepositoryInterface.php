<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\BudgetCategory;

/**
 * Interface for BudgetCategory repository operations.
 */
interface BudgetCategoryRepositoryInterface
{
    public function findById(int $id): ?BudgetCategory;
    
    public function findByUserId(int $userId): array;
    
    public function findByType(int $userId, string $type): array;
    
    public function findDefaults(): array;
    
    public function save(BudgetCategory $category): BudgetCategory;
    
    public function delete(int $id): bool;
}
