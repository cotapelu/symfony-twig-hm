<?php

declare(strict_types=1);

namespace App\Budgeting\Service;

use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\BudgetExpense;
use App\Budgeting\Repository\BudgetCategoryRepositoryInterface;
use App\Budgeting\Repository\BudgetExpenseRepositoryInterface;
use App\Budgeting\Repository\BudgetRepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Service for budgeting business logic.
 */
class BudgetingService
{
    private BudgetCategoryRepositoryInterface $categoryRepo;
    private BudgetRepositoryInterface $budgetRepo;
    private BudgetExpenseRepositoryInterface $expenseRepo;

    public function __construct(
        BudgetCategoryRepositoryInterface $categoryRepo,
        BudgetRepositoryInterface $budgetRepo,
        BudgetExpenseRepositoryInterface $expenseRepo
    ) {
        $this->categoryRepo = $categoryRepo;
        $this->budgetRepo = $budgetRepo;
        $this->expenseRepo = $expenseRepo;
    }

    // ==================== Category Methods ====================

    public function createCategory(
        string $name,
        string $type,
        string $color,
        int $userId,
        ?string $description = null
    ) {
        $this->validateCategoryType($type);
        
        $category = new BudgetCategory($name, $type, $color, $userId, $description);
        return $this->categoryRepo->save($category);
    }

    public function getUserCategories(int $userId)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        return $this->categoryRepo->findByUserId($userId);
    }

    public function getCategoriesByType(int $userId, string $type)
    {
        $this->validateCategoryType($type);
        
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        return $this->categoryRepo->findByType($userId, $type);
    }

    private function validateCategoryType(string $type)
    {
        $validTypes = ['income', 'expense', 'saving'];
        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid category type "%s". Must be one of: %s',
                $type,
                implode(', ', $validTypes)
            ));
        }
    }

    // ==================== Budget Methods ====================

    public function createBudget(
        int $userId,
        int $categoryId,
        float $amount,
        string $period,
        DateTimeInterface $startDate,
        ?string $note = null
    ) {
        $this->validateBudgetPeriod($period);
        
        if ($amount < 0) {
            throw new InvalidArgumentException('Budget amount cannot be negative');
        }

        $budget = new Budget($userId, $categoryId, $amount, $period, $startDate);
        
        if ($note !== null) {
            $budget->setNote($note);
        }

        return $this->budgetRepo->save($budget);
    }

    public function getUserBudgets(int $userId)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        return $this->budgetRepo->findByUserId($userId);
    }

    public function getActiveBudgets(int $userId)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        return $this->budgetRepo->findActiveByUserId($userId);
    }

    public function getBudgetProgress(int $budgetId)
    {
        $budget = $this->budgetRepo->findById($budgetId);
        if (!$budget) {
            throw new InvalidArgumentException('Budget not found');
        }

        // Get expenses for this user within the budget's date range
        $expenses = $this->expenseRepo->findByDateRange(
            $budget->getUserId(),
            $budget->getStartDate(),
            $budget->getEndDate()
        );
        
        $totalSpent = array_sum(array_map(fn($e) => $e->getAmount(), $expenses));
        $remaining = $budget->getAmount() - $totalSpent;
        $percentage = $totalSpent > 0 ? ($totalSpent / $budget->getAmount()) * 100 : 0;
        
        // Cap percentage at 100%
        if ($percentage > 100) {
            $percentage = 100;
        }

        return [
            'budget' => $budget,
            'total_spent' => $totalSpent,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'is_over_budget' => $totalSpent > $budget->getAmount(),
            'expense_count' => count($expenses),
        ];
    }

    private function validateBudgetPeriod(string $period)
    {
        $validPeriods = ['daily', 'weekly', 'monthly', 'yearly'];
        if (!in_array($period, $validPeriods, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid budget period "%s". Must be one of: %s',
                $period,
                implode(', ', $validPeriods)
            ));
        }
    }

    // ==================== Expense Methods ====================

    public function createExpense(
        int $userId,
        int $budgetId,
        int $categoryId,
        float $amount,
        DateTimeInterface $expenseDate,
        ?string $description = null,
        ?string $merchant = null
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be positive');
        }

        $expense = new BudgetExpense($userId, $budgetId, $categoryId, $amount, $expenseDate);
        
        if ($description !== null) {
            $expense->setDescription($description);
        }
        if ($merchant !== null) {
            $expense->setMerchant($merchant);
        }

        return $this->expenseRepo->save($expense);
    }

    public function getUserExpenses(int $userId, ?DateTimeInterface $start = null, ?DateTimeInterface $end = null)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        if ($start && $end) {
            return $this->expenseRepo->findByDateRange($userId, $start, $end);
        }

        return $this->expenseRepo->findByUserId($userId);
    }

    public function getSpendingSummary(int $userId, DateTimeInterface $start, DateTimeInterface $end)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        $expenses = $this->expenseRepo->findByDateRange($userId, $start, $end);
        
        $totalSpending = 0;
        $byCategory = [];
        $expenseCount = count($expenses);

        foreach ($expenses as $expense) {
            $amount = $expense->getAmount();
            $totalSpending += $amount;
            $categoryId = $expense->getCategoryId();

            if (!isset($byCategory[$categoryId])) {
                $byCategory[$categoryId] = [
                    'category_id' => $categoryId,
                    'total' => 0,
                    'count' => 0,
                ];
            }
            $byCategory[$categoryId]['total'] += $amount;
            $byCategory[$categoryId]['count']++;
        }

        return [
            'total_spending' => $totalSpending,
            'by_category' => array_values($byCategory),
            'expense_count' => $expenseCount,
        ];
    }

    // ==================== Admin/Helper Methods ====================

    public function getCategoryRepository()
    {
        return $this->categoryRepo;
    }

    public function getBudgetRepository()
    {
        return $this->budgetRepo;
    }

    public function getExpenseRepository()
    {
        return $this->expenseRepo;
    }

    public function saveCategory(BudgetCategory $category)
    {
        return $this->categoryRepo->save($category);
    }

    public function saveBudget(Budget $budget)
    {
        return $this->budgetRepo->save($budget);
    }

    public function saveExpense(BudgetExpense $expense)
    {
        return $this->expenseRepo->save($expense);
    }

    public function deleteCategory(int $id, int $userId)
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category || $category->getUserId() !== $userId) {
            throw new InvalidArgumentException('Category not found');
        }
        $this->categoryRepo->delete($id);
    }

    public function deleteBudget(int $id, int $userId)
    {
        $budget = $this->budgetRepo->findById($id);
        if (!$budget || $budget->getUserId() !== $userId) {
            throw new InvalidArgumentException('Budget not found');
        }
        $this->budgetRepo->delete($id);
    }

    public function deleteExpense(int $id, int $userId)
    {
        $expense = $this->expenseRepo->findById($id);
        if (!$expense || $expense->getUserId() !== $userId) {
            throw new InvalidArgumentException('Expense not found');
        }
        $this->expenseRepo->delete($id);
    }
}
