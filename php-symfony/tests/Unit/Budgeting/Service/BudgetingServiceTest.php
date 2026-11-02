<?php declare(strict_types=1);

namespace App\Tests\Unit\Budgeting\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Entity\{Budget, BudgetCategory, BudgetExpense};
use App\Budgeting\Repository\BudgetCategoryRepositoryInterface;
use App\Budgeting\Repository\BudgetRepositoryInterface;
use App\Budgeting\Repository\BudgetExpenseRepositoryInterface;
use App\Budgeting\Service\BudgetingService;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class BudgetingServiceTest extends TestCase
{
    private BudgetCategoryRepositoryInterface $categoryRepoMock;
    private BudgetRepositoryInterface $budgetRepoMock;
    private BudgetExpenseRepositoryInterface $expenseRepoMock;
    private BudgetingService $service;

    protected function setUp(): void
    {
        $this->categoryRepoMock = $this->createMock(BudgetCategoryRepositoryInterface::class);
        $this->budgetRepoMock = $this->createMock(BudgetRepositoryInterface::class);
        $this->expenseRepoMock = $this->createMock(BudgetExpenseRepositoryInterface::class);

        $this->service = new BudgetingService(
            $this->categoryRepoMock,
            $this->budgetRepoMock,
            $this->expenseRepoMock
        );
    }

    // Helper to create category
    private function createCategory(?int $id = null, string $name = 'Test', string $type = 'expense', string $color = '#000', int $userId = 1, ?string $description = null): BudgetCategory
    {
        $c = new BudgetCategory($name, $type, $color, $userId, $description);
        if ($id !== null) {
            $ref = new \ReflectionClass($c);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($c, $id);
        }
        return $c;
    }

    // Helper to create budget
    private function createBudget(?int $id = null, int $userId = 1, int $categoryId = 1, float $amount = 1000.0, string $period = 'monthly', ?DateTimeInterface $start = null, ?string $note = null): Budget
    {
        $start ??= new DateTimeImmutable('first day of this month');
        $b = new Budget($userId, $categoryId, $amount, $period, $start);
        if ($note !== null) {
            $b->setNote($note);
        }
        if ($id !== null) {
            $ref = new \ReflectionClass($b);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($b, $id);
        }
        return $b;
    }

    // Helper to create expense
    private function createExpense(?int $id = null, int $userId = 1, int $budgetId = 1, int $categoryId = 1, float $amount = 100.0, ?DateTimeInterface $date = null): BudgetExpense
    {
        $date ??= new DateTimeImmutable();
        $e = new BudgetExpense($userId, $budgetId, $categoryId, $amount, $date);
        if ($id !== null) {
            $ref = new \ReflectionClass($e);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($e, $id);
        }
        return $e;
    }

    // ==================== Category Tests ====================

    public function testCreateCategorySuccess(): void
    {
        $this->categoryRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(BudgetCategory::class))->willReturnArgument(0);

        $result = $this->service->createCategory('Groceries', 'expense', '#0f0', 1, 'Food');

        $this->assertInstanceOf(BudgetCategory::class, $result);
        $this->assertSame('Groceries', $result->getName());
        $this->assertSame('expense', $result->getType());
        $this->assertSame('#0f0', $result->getColor());
        $this->assertSame(1, $result->getUserId());
        $this->assertSame('Food', $result->getDescription());
    }

    public function testCreateCategoryThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category type');

        $this->service->createCategory('Test', 'invalid', '#000', 1);
    }

    public function testGetUserCategoriesThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');
        $this->service->getUserCategories(0);
    }

    public function testGetUserCategories(): void
    {
        $cats = [$this->createCategory(1), $this->createCategory(2)];
        $this->categoryRepoMock->expects($this->once())->method('findByUserId')->with(5)->willReturn($cats);

        $result = $this->service->getUserCategories(5);

        $this->assertSame($cats, $result);
    }

    public function testGetCategoriesByTypeThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getCategoriesByType(1, 'badtype');
    }

    public function testGetCategoriesByTypeThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getCategoriesByType(0, 'expense');
    }

    public function testGetCategoriesByType(): void
    {
        $cats = [$this->createCategory(1)];
        $this->categoryRepoMock->expects($this->once())->method('findByType')->with(5, 'income')->willReturn($cats);

        $result = $this->service->getCategoriesByType(5, 'income');

        $this->assertSame($cats, $result);
    }

    // ==================== Budget Tests ====================

    public function testCreateBudgetSuccess(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $this->budgetRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(Budget::class))->willReturnArgument(0);

        $result = $this->service->createBudget(1, 1, 500.0, 'monthly', $start, 'Note');

        $this->assertInstanceOf(Budget::class, $result);
        $this->assertSame(1, $result->getUserId());
        $this->assertSame(1, $result->getCategoryId());
        $this->assertSame(500.0, $result->getAmount());
        $this->assertSame('monthly', $result->getPeriod());
        $this->assertEquals($start, $result->getStartDate());
        $this->assertSame('Note', $result->getNote());
        // endDate should be start + 1 month
        $this->assertEquals('2025-02-01', $result->getEndDate()->format('Y-m-d'));
    }

    public function testCreateBudgetThrowsOnNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget amount cannot be negative');
        $this->service->createBudget(1, 1, -100.0, 'monthly', new DateTimeImmutable());
    }

    public function testCreateBudgetThrowsOnInvalidPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid budget period');
        $this->service->createBudget(1, 1, 100.0, 'bad', new DateTimeImmutable());
    }

    public function testGetUserBudgetsThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getUserBudgets(0);
    }

    public function testGetUserBudgets(): void
    {
        $budgets = [$this->createBudget(1), $this->createBudget(2)];
        $this->budgetRepoMock->expects($this->once())->method('findByUserId')->with(5)->willReturn($budgets);

        $result = $this->service->getUserBudgets(5);

        $this->assertSame($budgets, $result);
    }

    public function testGetActiveBudgetsThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getActiveBudgets(0);
    }

    public function testGetActiveBudgets(): void
    {
        $budgets = [$this->createBudget(1)];
        $this->budgetRepoMock->expects($this->once())->method('findActiveByUserId')->with(5)->willReturn($budgets);

        $result = $this->service->getActiveBudgets(5);

        $this->assertSame($budgets, $result);
    }

    public function testGetBudgetProgressSuccess(): void
    {
        $budget = $this->createBudget(1, 1, 1, 1000.0);
        $expense1 = $this->createExpense(1, 1, 1, 1, 300.0);
        $expense2 = $this->createExpense(2, 1, 1, 1, 200.0);

        $this->budgetRepoMock->expects($this->once())->method('findById')->with(10)->willReturn($budget);
        $this->expenseRepoMock->expects($this->once())->method('findByDateRange')->with(1, $budget->getStartDate(), $budget->getEndDate())->willReturn([$expense1, $expense2]);

        $result = $this->service->getBudgetProgress(10);

        $this->assertSame($budget, $result['budget']);
        $this->assertSame(500.0, $result['total_spent']);
        $this->assertSame(500.0, $result['remaining']);
        $this->assertSame(50.0, $result['percentage']);
        $this->assertFalse($result['is_over_budget']);
        $this->assertSame(2, $result['expense_count']);
    }

    public function testGetBudgetProgressThrowsIfNotFound(): void
    {
        $this->budgetRepoMock->expects($this->once())->method('findById')->with(99)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget not found');

        $this->service->getBudgetProgress(99);
    }

    public function testGetBudgetProgressWithNoExpenses(): void
    {
        $budget = $this->createBudget(1, 1, 1, 1000.0);
        $this->budgetRepoMock->method('findById')->willReturn($budget);
        $this->expenseRepoMock->method('findByDateRange')->willReturn([]);

        $result = $this->service->getBudgetProgress(1);

        $this->assertEquals(0.0, $result['total_spent']);
        $this->assertSame(1000.0, $result['remaining']);
        $this->assertEquals(0.0, $result['percentage']);
        $this->assertFalse($result['is_over_budget']);
        $this->assertSame(0, $result['expense_count']);
    }

    public function testGetBudgetProgressWithOverBudget(): void
    {
        $budget = $this->createBudget(1, 1, 1, 100.0);
        $expense = $this->createExpense(1, 1, 1, 1, 150.0);
        $this->budgetRepoMock->method('findById')->willReturn($budget);
        $this->expenseRepoMock->method('findByDateRange')->willReturn([$expense]);

        $result = $this->service->getBudgetProgress(1);

        $this->assertSame(150.0, $result['total_spent']);
        $this->assertSame(-50.0, $result['remaining']);
        $this->assertEquals(100, $result['percentage']); // capped at 100
        $this->assertTrue($result['is_over_budget']);
    }

    // ==================== Expense Tests ====================

    public function testCreateExpenseSuccess(): void
    {
        $this->expenseRepoMock->expects($this->once())->method('save')->with($this->isInstanceOf(BudgetExpense::class))->willReturnArgument(0);

        $date = new DateTimeImmutable('2025-06-10');
        $result = $this->service->createExpense(1, 1, 1, 150.0, $date, 'Dinner', 'Restaurant');

        $this->assertInstanceOf(BudgetExpense::class, $result);
        $this->assertSame(1, $result->getUserId());
        $this->assertSame(1, $result->getBudgetId());
        $this->assertSame(1, $result->getCategoryId());
        $this->assertSame(150.0, $result->getAmount());
        $this->assertSame('Dinner', $result->getDescription());
        $this->assertSame('Restaurant', $result->getMerchant());
        $this->assertEquals($date, $result->getExpenseDate());
    }

    public function testCreateExpenseThrowsOnAmountZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expense amount must be positive');
        $this->service->createExpense(1, 1, 1, 0.0, new DateTimeImmutable());
    }

    public function testCreateExpenseThrowsOnNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expense amount must be positive');
        $this->service->createExpense(1, 1, 1, -10.0, new DateTimeImmutable());
    }

    public function testGetUserExpensesThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getUserExpenses(0);
    }

    public function testGetUserExpensesWithoutDates(): void
    {
        $expenses = [$this->createExpense(1), $this->createExpense(2)];
        $this->expenseRepoMock->expects($this->once())->method('findByUserId')->with(5)->willReturn($expenses);

        $result = $this->service->getUserExpenses(5);

        $this->assertSame($expenses, $result);
    }

    public function testGetUserExpensesWithDates(): void
    {
        $expenses = [$this->createExpense(1)];
        $start = new DateTimeImmutable('2025-06-01');
        $end = new DateTimeImmutable('2025-06-30');
        $this->expenseRepoMock->expects($this->once())->method('findByDateRange')->with(5, $start, $end)->willReturn($expenses);

        $result = $this->service->getUserExpenses(5, $start, $end);

        $this->assertSame($expenses, $result);
    }

    public function testGetSpendingSummary(): void
    {
        $expense1 = $this->createExpense(1, 1, 1, 1, 100.0);
        $expense2 = $this->createExpense(2, 1, 2, 2, 200.0);
        $expenses = [$expense1, $expense2];
        $start = new DateTimeImmutable('2025-06-01');
        $end = new DateTimeImmutable('2025-06-30');

        $this->expenseRepoMock->expects($this->once())->method('findByDateRange')->with(1, $start, $end)->willReturn($expenses);

        $result = $this->service->getSpendingSummary(1, $start, $end);

        $this->assertEquals(300.0, $result['total_spending']);
        $this->assertCount(2, $result['by_category']);
        // array_values re-indexes; find entries by category_id
        $cat1 = null; $cat2 = null;
        foreach ($result['by_category'] as $c) {
            if ($c['category_id'] == 1) $cat1 = $c;
            if ($c['category_id'] == 2) $cat2 = $c;
        }
        $this->assertNotNull($cat1);
        $this->assertEquals(100.0, $cat1['total']);
        $this->assertEquals(1, $cat1['count']);
        $this->assertNotNull($cat2);
        $this->assertEquals(200.0, $cat2['total']);
        $this->assertEquals(1, $cat2['count']);
        $this->assertSame(2, $result['expense_count']);
    }

    public function testGetSpendingSummaryThrowsOnInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSpendingSummary(0, new DateTimeImmutable(), new DateTimeImmutable());
    }

    // ==================== Delete Tests ====================

    public function testDeleteCategoryThrowsIfNotFoundOrWrongUser(): void
    {
        $this->categoryRepoMock->expects($this->once())->method('findById')->with(10)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category not found');
        $this->service->deleteCategory(10, 1);
    }

    public function testDeleteCategoryThrowsIfUserMismatch(): void
    {
        $cat = $this->createCategory(10, 'Test', 'expense', '#000', 2);
        $this->categoryRepoMock->method('findById')->willReturn($cat);

        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteCategory(10, 1);
    }

    public function testDeleteCategorySuccess(): void
    {
        $cat = $this->createCategory(10, 'Test', 'expense', '#000', 1);
        $this->categoryRepoMock->method('findById')->willReturn($cat);
        $this->categoryRepoMock->expects($this->once())->method('delete')->with(10);
        $this->service->deleteCategory(10, 1);
        $this->assertTrue(true);
    }

    public function testDeleteBudgetThrowsIfNotFound(): void
    {
        $this->budgetRepoMock->expects($this->once())->method('findById')->with(10)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteBudget(10, 1);
    }

    public function testDeleteBudgetThrowsIfUserMismatch(): void
    {
        $budget = $this->createBudget(10, 2, 1, 100.0);
        $this->budgetRepoMock->method('findById')->willReturn($budget);
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteBudget(10, 1);
    }

    public function testDeleteBudgetSuccess(): void
    {
        $budget = $this->createBudget(10, 1, 1, 100.0);
        $this->budgetRepoMock->method('findById')->willReturn($budget);
        $this->budgetRepoMock->expects($this->once())->method('delete')->with(10);
        $this->service->deleteBudget(10, 1);
        $this->assertTrue(true);
    }

    public function testDeleteExpenseThrowsIfNotFound(): void
    {
        $this->expenseRepoMock->expects($this->once())->method('findById')->with(10)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteExpense(10, 1);
    }

    public function testDeleteExpenseThrowsIfUserMismatch(): void
    {
        $expense = $this->createExpense(10, 2, 1, 1, 100.0);
        $this->expenseRepoMock->method('findById')->willReturn($expense);
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteExpense(10, 1);
    }

    public function testDeleteExpenseSuccess(): void
    {
        $expense = $this->createExpense(10, 1, 1, 1, 100.0);
        $this->expenseRepoMock->method('findById')->willReturn($expense);
        $this->expenseRepoMock->expects($this->once())->method('delete')->with(10);
        $this->service->deleteExpense(10, 1);
        $this->assertTrue(true);
    }

    // ==================== Getter Tests ====================

    public function testGetCategoryRepository(): void
    {
        $this->assertSame($this->categoryRepoMock, $this->service->getCategoryRepository());
    }

    public function testGetBudgetRepository(): void
    {
        $this->assertSame($this->budgetRepoMock, $this->service->getBudgetRepository());
    }

    public function testGetExpenseRepository(): void
    {
        $this->assertSame($this->expenseRepoMock, $this->service->getExpenseRepository());
    }
}
