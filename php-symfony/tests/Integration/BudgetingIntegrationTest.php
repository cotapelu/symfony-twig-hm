<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\BudgetExpense;
use App\Budgeting\Repository\BudgetCategoryRepository;
use App\Budgeting\Repository\BudgetExpenseRepository;
use App\Budgeting\Repository\BudgetRepository;
use App\Budgeting\Service\BudgetingService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Budgeting\Integration
 * 
 * Integration tests for Budgeting module using real implementations
 * with SQLite in-memory database.
 */
#[AllowMockObjectsWithoutExpectations]
class BudgetingIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private BudgetCategoryRepository $categoryRepo;
    private BudgetRepository $budgetRepo;
    private BudgetExpenseRepository $expenseRepo;
    private BudgetingService $service;

    protected function setUp(): void
    {
        // Create in-memory SQLite database
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create tables
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS budget_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                color VARCHAR(20) NOT NULL,
                user_id INTEGER NOT NULL,
                parent_id INTEGER,
                is_default BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS budgets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                period VARCHAR(20) NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                note TEXT,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS budget_expenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                budget_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                description TEXT,
                merchant TEXT,
                transaction_id INTEGER,
                expense_date DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        // Initialize repositories with real PDO connection
        $this->categoryRepo = new BudgetCategoryRepository($this->pdo);
        $this->budgetRepo = new BudgetRepository($this->pdo);
        $this->expenseRepo = new BudgetExpenseRepository($this->pdo);

        // Initialize service with real repositories
        $this->service = new BudgetingService(
            $this->categoryRepo,
            $this->budgetRepo,
            $this->expenseRepo
        );
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== Category Integration Tests ====================

    public function testCreateCategoryAndRetrieve(): void
    {
        // Create category
        $category = $this->service->createCategory(
            'Food',
            'expense',
            '#FF0000',
            1,
            'Food expenses tracking'
        );

        $this->assertNotNull($category->getId());
        $this->assertEquals('Food', $category->getName());
        $this->assertEquals('expense', $category->getType());
        $this->assertEquals('#FF0000', $category->getColor());
        $this->assertEquals(1, $category->getUserId());
        $this->assertEquals('Food expenses tracking', $category->getDescription());

        // Retrieve and verify
        $retrieved = $this->categoryRepo->findById($category->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('Food', $retrieved->getName());
    }

    public function testGetUserCategoriesReturnsAll(): void
    {
        // Create multiple categories for user
        $this->service->createCategory('Food', 'expense', '#FF0000', 1);
        $this->service->createCategory('Transport', 'expense', '#00FF00', 1);
        $this->service->createCategory('Salary', 'income', '#0000FF', 1);
        $this->service->createCategory('Food', 'expense', '#FF0000', 2); // Different user

        // Get categories for user 1
        $categories = $this->service->getUserCategories(1);

        $this->assertCount(3, $categories);
        $this->assertContainsOnlyInstancesOf(BudgetCategory::class, $categories);
    }

    public function testGetCategoriesByTypeFilter(): void
    {
        // Create categories
        $this->service->createCategory('Salary', 'income', '#0000FF', 1);
        $this->service->createCategory('Bonus', 'income', '#00FFFF', 1);
        $this->service->createCategory('Food', 'expense', '#FF0000', 1);

        // Filter by type
        $incomeCategories = $this->service->getCategoriesByType(1, 'income');
        $expenseCategories = $this->service->getCategoriesByType(1, 'expense');

        $this->assertCount(2, $incomeCategories);
        $this->assertCount(1, $expenseCategories);

        foreach ($incomeCategories as $cat) {
            $this->assertEquals('income', $cat->getType());
        }

        foreach ($expenseCategories as $cat) {
            $this->assertEquals('expense', $cat->getType());
        }
    }

    // ==================== Budget Integration Tests ====================

    public function testCreateBudgetAndRetrieve(): void
    {
        $startDate = new \DateTime('2026-01-01');

        // Create budget
        $budget = $this->service->createBudget(
            1,
            1,
            1000.00,
            'monthly',
            $startDate,
            'Monthly food budget'
        );

        $this->assertNotNull($budget->getId());
        $this->assertEquals(1, $budget->getUserId());
        $this->assertEquals(1, $budget->getCategoryId());
        $this->assertEquals(1000.00, $budget->getAmount());
        $this->assertEquals('monthly', $budget->getPeriod());
        $this->assertEquals($startDate, $budget->getStartDate());
        $this->assertEquals('Monthly food budget', $budget->getNote());
        $this->assertTrue($budget->isActive());

        // Verify end date is calculated correctly (1 month later)
        $expectedEnd = clone $startDate;
        $expectedEnd->modify('+1 month');
        $this->assertEquals($expectedEnd, $budget->getEndDate());
    }

    public function testGetUserBudgetsReturnsAll(): void
    {
        $startDate = new \DateTime('2026-01-01');

        // Create multiple budgets for user
        $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);
        $this->service->createBudget(1, 2, 500.00, 'weekly', $startDate);
        $this->service->createBudget(2, 1, 2000.00, 'yearly', $startDate); // Different user

        // Get budgets for user 1
        $budgets = $this->service->getUserBudgets(1);

        $this->assertCount(2, $budgets);
        $this->assertContainsOnlyInstancesOf(Budget::class, $budgets);
    }

    public function testGetBudgetProgressEmptyExpenses(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        // Get progress (no expenses yet)
        $progress = $this->service->getBudgetProgress($budget->getId());

        $this->assertEquals(0.0, $progress['total_spent']);
        $this->assertEquals(1000.00, $progress['remaining']);
        $this->assertEquals(0.0, $progress['percentage']);
        $this->assertFalse($progress['is_over_budget']);
        $this->assertEquals(0, $progress['expense_count']);
    }

    public function testGetBudgetProgressWithExpenses(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        // Add expenses
        $this->service->createExpense(
            1,
            $budget->getId(),
            1,
            250.00,
            new \DateTime('2026-01-15'),
            'Lunch',
            'Restaurant A'
        );

        $this->service->createExpense(
            1,
            $budget->getId(),
            1,
            150.50,
            new \DateTime('2026-01-20'),
            'Groceries',
            'Supermarket B'
        );

        // Get progress
        $progress = $this->service->getBudgetProgress($budget->getId());

        $this->assertEqualsWithDelta(400.50, $progress['total_spent'], 0.01);
        $this->assertEqualsWithDelta(599.50, $progress['remaining'], 0.01);
        $this->assertEqualsWithDelta(40.05, $progress['percentage'], 0.01);
        $this->assertFalse($progress['is_over_budget']);
        $this->assertEquals(2, $progress['expense_count']);
    }

    public function testGetBudgetProgressOverBudget(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 100.00, 'monthly', $startDate);

        // Add expense exceeding budget
        $this->service->createExpense(
            1,
            $budget->getId(),
            1,
            150.00,
            new \DateTime('2026-01-15'),
            'Big purchase',
            'Store C'
        );

        $progress = $this->service->getBudgetProgress($budget->getId());

        $this->assertEquals(150.00, $progress['total_spent']);
        $this->assertEquals(-50.00, $progress['remaining']);
        $this->assertEquals(100.0, $progress['percentage']); // Capped at 100%
        $this->assertTrue($progress['is_over_budget']);
    }

    public function testGetBudgetProgressNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget not found');

        $this->service->getBudgetProgress(99999);
    }

    // ==================== Expense Integration Tests ====================

    public function testCreateExpenseAndRetrieve(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);
        $expenseDate = new \DateTime('2026-01-15');

        // Create expense
        $expense = $this->service->createExpense(
            1,
            $budget->getId(),
            1,
            250.00,
            $expenseDate,
            'Lunch meeting',
            'Restaurant ABC'
        );

        $this->assertNotNull($expense->getId());
        $this->assertEquals(1, $expense->getUserId());
        $this->assertEquals($budget->getId(), $expense->getBudgetId());
        $this->assertEquals(1, $expense->getCategoryId());
        $this->assertEquals(250.00, $expense->getAmount());
        $this->assertEquals('Lunch meeting', $expense->getDescription());
        $this->assertEquals('Restaurant ABC', $expense->getMerchant());
        $this->assertEquals($expenseDate, $expense->getExpenseDate());
    }

    public function testGetUserExpensesAll(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        // Create expenses
        $this->service->createExpense(1, $budget->getId(), 1, 100.00, new \DateTime('2026-01-05'));
        $this->service->createExpense(1, $budget->getId(), 1, 200.00, new \DateTime('2026-01-10'));
        $this->service->createExpense(2, $budget->getId(), 1, 50.00, new \DateTime('2026-01-15')); // Different user

        $expenses = $this->service->getUserExpenses(1);

        $this->assertCount(2, $expenses);
        $this->assertContainsOnlyInstancesOf(BudgetExpense::class, $expenses);
    }

    public function testGetUserExpensesWithDateRange(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        // Create expenses
        $this->service->createExpense(1, $budget->getId(), 1, 100.00, new \DateTime('2026-01-05'));
        $this->service->createExpense(1, $budget->getId(), 1, 200.00, new \DateTime('2026-01-15'));
        $this->service->createExpense(1, $budget->getId(), 1, 300.00, new \DateTime('2026-01-25'));

        // Filter by date range
        $startFilter = new \DateTime('2026-01-10');
        $endFilter = new \DateTime('2026-01-20');

        $expenses = $this->service->getUserExpenses(1, $startFilter, $endFilter);

        $this->assertCount(1, $expenses);
        $this->assertEquals(200.00, $expenses[0]->getAmount());
    }

    public function testGetSpendingSummary(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        // Create expenses in different categories
        $this->service->createExpense(1, $budget->getId(), 1, 100.00, new \DateTime('2026-01-05'), 'Food A');
        $this->service->createExpense(1, $budget->getId(), 1, 150.00, new \DateTime('2026-01-10'), 'Food B');
        $this->service->createExpense(1, $budget->getId(), 2, 200.00, new \DateTime('2026-01-15'), 'Transport A'); // Different category
        $this->service->createExpense(1, $budget->getId(), 3, 50.00, new \DateTime('2026-01-20'), 'Entertainment'); // Different category

        $start = new \DateTime('2026-01-01');
        $end = new \DateTime('2026-01-31');

        $summary = $this->service->getSpendingSummary(1, $start, $end);

        $this->assertEquals(500.00, $summary['total_spending']);
        $this->assertCount(3, $summary['by_category']);
        $this->assertEquals(4, $summary['expense_count']);

        // Verify category totals
        $catTotals = [];
        foreach ($summary['by_category'] as $cat) {
            $catTotals[$cat['category_id']] = $cat['total'];
        }

        $this->assertEquals(250.00, $catTotals[1]); // Category 1: 100 + 150
        $this->assertEquals(200.00, $catTotals[2]); // Category 2: 200
        $this->assertEquals(50.00, $catTotals[3]);  // Category 3: 50
    }

    // ==================== Edge Case Tests ====================

    public function testBudgetWithZeroAmount(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 0.00, 'monthly', $startDate);

        $this->assertEquals(0.00, $budget->getAmount());
    }

    public function testBudgetWithDifferentPeriods(): void
    {
        $startDate = new \DateTime('2026-01-01');

        $monthlyBudget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);
        $monthlyEnd = clone $startDate;
        $monthlyEnd->modify('+1 month');
        $this->assertEquals($monthlyEnd, $monthlyBudget->getEndDate());

        $weeklyBudget = $this->service->createBudget(1, 2, 500.00, 'weekly', $startDate);
        $weeklyEnd = clone $startDate;
        $weeklyEnd->modify('+1 week');
        $this->assertEquals($weeklyEnd, $weeklyBudget->getEndDate());

        $yearlyBudget = $this->service->createBudget(1, 3, 12000.00, 'yearly', $startDate);
        $yearlyEnd = clone $startDate;
        $yearlyEnd->modify('+1 year');
        $this->assertEquals($yearlyEnd, $yearlyBudget->getEndDate());
    }

    public function testExpenseWithoutDescription(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);

        $expense = $this->service->createExpense(
            1,
            $budget->getId(),
            1,
            100.00,
            new \DateTime('2026-01-15'),
            null,
            null
        );

        $this->assertNull($expense->getDescription());
        $this->assertNull($expense->getMerchant());
    }

    public function testMultipleCategoriesForUser(): void
    {
        // Create multiple categories
        $food = $this->service->createCategory('Food', 'expense', '#FF0000', 1);
        $transport = $this->service->createCategory('Transport', 'expense', '#00FF00', 1);
        $entertainment = $this->service->createCategory('Entertainment', 'expense', '#0000FF', 1);
        $salary = $this->service->createCategory('Salary', 'income', '#FFFF00', 1);

        // Get all categories
        $categories = $this->service->getUserCategories(1);

        $this->assertCount(4, $categories);

        // Verify all types
        $types = array_unique(array_map(fn($c) => $c->getType(), $categories));
        $this->assertCount(2, $types);
        $this->assertContains('expense', $types);
        $this->assertContains('income', $types);
    }

    // ==================== Data Integrity Tests ====================

    public function testCategoryIsolationBetweenUsers(): void
    {
        // Create categories for different users
        $user1Food = $this->service->createCategory('Food', 'expense', '#FF0000', 1);
        $user2Food = $this->service->createCategory('Food', 'expense', '#00FF00', 2);

        // Retrieve for each user
        $user1Categories = $this->service->getUserCategories(1);
        $user2Categories = $this->service->getUserCategories(2);

        $this->assertCount(1, $user1Categories);
        $this->assertCount(1, $user2Categories);
        $this->assertEquals('#FF0000', $user1Categories[0]->getColor());
        $this->assertEquals('#00FF00', $user2Categories[0]->getColor());
    }

    public function testBudgetIsolationBetweenUsers(): void
    {
        $startDate = new \DateTime('2026-01-01');

        // Create budgets for different users
        $user1Budget = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);
        $user2Budget = $this->service->createBudget(2, 1, 2000.00, 'monthly', $startDate);

        // Retrieve for each user
        $user1Budgets = $this->service->getUserBudgets(1);
        $user2Budgets = $this->service->getUserBudgets(2);

        $this->assertCount(1, $user1Budgets);
        $this->assertCount(1, $user2Budgets);
        $this->assertEquals(1000.00, $user1Budgets[0]->getAmount());
        $this->assertEquals(2000.00, $user2Budgets[0]->getAmount());
    }

    public function testExpenseIsolationBetweenUsers(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget1 = $this->service->createBudget(1, 1, 1000.00, 'monthly', $startDate);
        $budget2 = $this->service->createBudget(2, 1, 2000.00, 'monthly', $startDate);

        // Create expenses for each user
        $this->service->createExpense(1, $budget1->getId(), 1, 100.00, new \DateTime('2026-01-15'));
        $this->service->createExpense(1, $budget1->getId(), 1, 150.00, new \DateTime('2026-01-20'));
        $this->service->createExpense(2, $budget2->getId(), 1, 300.00, new \DateTime('2026-01-15'));

        // Get expenses for user 1
        $user1Expenses = $this->service->getUserExpenses(1);

        $this->assertCount(2, $user1Expenses);
        $totalUser1 = array_sum(array_map(fn($e) => $e->getAmount(), $user1Expenses));
        $this->assertEquals(250.00, $totalUser1);
    }
}
