<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\BudgetExpense;
use DateTimeInterface;

/**
 * Interface for BudgetExpense repository operations.
 */
interface BudgetExpenseRepositoryInterface
{
    public function findById(int $id): ?BudgetExpense;
    
    public function findByUserId(int $userId): array;
    
    public function findByBudgetId(int $budgetId): array;
    
    public function findByDateRange(int $userId, DateTimeInterface $start, DateTimeInterface $end): array;
    
    public function save(BudgetExpense $expense): BudgetExpense;
    
    public function delete(int $id): bool;
}
