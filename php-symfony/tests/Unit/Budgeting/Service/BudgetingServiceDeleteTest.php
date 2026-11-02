<?php declare(strict_types=1);

namespace App\Tests\Unit\Budgeting\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Budgeting\Service\BudgetingService;
use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetExpense;
use App\Budgeting\Repository\BudgetCategoryRepositoryInterface;
use App\Budgeting\Repository\BudgetRepositoryInterface;
use App\Budgeting\Repository\BudgetExpenseRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class BudgetingServiceDeleteTest extends TestCase
{
    private BudgetCategoryRepositoryInterface&MockObject $categoryRepoMock;
    private BudgetRepositoryInterface&MockObject $budgetRepoMock;
    private BudgetExpenseRepositoryInterface&MockObject $expenseRepoMock;
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

    // Helper to create category entity with optional ID
    private function createCategory(?int $id = null, int $userId = 1, string $name = 'Test', string $type = 'expense'): BudgetCategory
    {
        $c = new BudgetCategory($name, $type, '#000', $userId);
        if ($id !== null) {
            $ref = new \ReflectionProperty($c, 'id');
            $ref->setAccessible(true);
            $ref->setValue($c, $id);
        }
        return $c;
    }

    // Helper to create budget entity with optional ID
    private function createBudget(?int $id = null, int $userId = 1, int $categoryId = 1, float $amount = 100.0): Budget
    {
        $start = new DateTimeImmutable();
        $b = new Budget($userId, $categoryId, $amount, 'monthly', $start);
        if ($id !== null) {
            $ref = new \ReflectionProperty($b, 'id');
            $ref->setAccessible(true);
            $ref->setValue($b, $id);
        }
        return $b;
    }

    // Helper to create expense entity with optional ID
    private function createExpense(?int $id = null, int $userId = 1, int $budgetId = 1, int $categoryId = 1, float $amount = 50.0): BudgetExpense
    {
        $date = new DateTimeImmutable();
        $e = new BudgetExpense($userId, $budgetId, $categoryId, $amount, $date);
        if ($id !== null) {
            $ref = new \ReflectionProperty($e, 'id');
            $ref->setAccessible(true);
            $ref->setValue($e, $id);
        }
        return $e;
    }

    // ==================== Category Delete Tests ====================

    public function testDeleteCategorySuccess(): void
    {
        $category = $this->createCategory(5, 1);
        $this->categoryRepoMock->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($category);
        $this->categoryRepoMock->expects($this->once())
            ->method('delete')
            ->with(5);

        $this->service->deleteCategory(5, 1);
        // Expectations verified, no exception thrown
    }

    public function testDeleteCategoryNotFound(): void
    {
        $this->categoryRepoMock->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category not found');

        $this->service->deleteCategory(99, 1);
    }

    public function testDeleteCategoryWrongUser(): void
    {
        $category = $this->createCategory(5, 2); // belongs to user 2
        $this->categoryRepoMock->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($category);
        $this->categoryRepoMock->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category not found');

        $this->service->deleteCategory(5, 1);
    }

    // ==================== Budget Delete Tests ====================

    public function testDeleteBudgetSuccess(): void
    {
        $budget = $this->createBudget(10, 1, 1, 500.0);
        $this->budgetRepoMock->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($budget);
        $this->budgetRepoMock->expects($this->once())
            ->method('delete')
            ->with(10);

        $this->service->deleteBudget(10, 1);
    }

    public function testDeleteBudgetNotFound(): void
    {
        $this->budgetRepoMock->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget not found');

        $this->service->deleteBudget(99, 1);
    }

    public function testDeleteBudgetWrongUser(): void
    {
        $budget = $this->createBudget(10, 2, 1, 500.0);
        $this->budgetRepoMock->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($budget);
        $this->budgetRepoMock->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget not found');

        $this->service->deleteBudget(10, 1);
    }

    // ==================== Expense Delete Tests ====================

    public function testDeleteExpenseSuccess(): void
    {
        $expense = $this->createExpense(20, 1, 1, 1, 75.0);
        $this->expenseRepoMock->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($expense);
        $this->expenseRepoMock->expects($this->once())
            ->method('delete')
            ->with(20);

        $this->service->deleteExpense(20, 1);
    }

    public function testDeleteExpenseNotFound(): void
    {
        $this->expenseRepoMock->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expense not found');

        $this->service->deleteExpense(99, 1);
    }

    public function testDeleteExpenseWrongUser(): void
    {
        $expense = $this->createExpense(20, 2, 1, 1, 75.0);
        $this->expenseRepoMock->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($expense);
        $this->expenseRepoMock->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expense not found');

        $this->service->deleteExpense(20, 1);
    }
}
