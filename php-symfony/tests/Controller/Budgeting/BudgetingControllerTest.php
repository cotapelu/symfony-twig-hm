<?php

declare(strict_types=1);

namespace App\Tests\Controller\Budgeting;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Budgeting\Controller\BudgetingController;
use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\BudgetExpense;
use App\Budgeting\Service\BudgetingService;
use App\Budgeting\Repository\BudgetRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class BudgetingControllerTest extends TestCase
{
    private BudgetingController $controller;
    private BudgetingService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(BudgetingService::class);
        $this->controller = new BudgetingController($this->service);
    }

    // ==================== Category Tests ====================

    public function testGetCategoriesReturnsAll(): void
    {
        $categories = [
            new BudgetCategory('Food', 'expense', '#FF0000', 1),
            new BudgetCategory('Transport', 'expense', '#00FF00', 1),
        ];
        $this->service->method('getUserCategories')->willReturn($categories);

        $request = Request::create('/api/budgeting/categories', 'GET', ['user_id' => 1]);
        $response = $this->controller->getCategoriesAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('Food', $result['data'][0]['name']);
        $this->assertEquals('expense', $result['data'][0]['type']);
    }

    public function testGetCategoriesWithTypeFilter(): void
    {
        $categories = [
            new BudgetCategory('Salary', 'income', '#0000FF', 1),
        ];
        $this->service->method('getCategoriesByType')->willReturn($categories);

        $request = Request::create('/api/budgeting/categories', 'GET', ['user_id' => 1, 'type' => 'income']);
        $response = $this->controller->getCategoriesAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('income', $result['data'][0]['type']);
    }

    public function testGetCategoryFound(): void
    {
        $category = $this->createMock(BudgetCategory::class);
        $category->method('getId')->willReturn(1);
        $category->method('getName')->willReturn('Food');
        $this->service->method('getUserCategories')->willReturn([$category]);

        $request = Request::create('/api/budgeting/categories/1', 'GET', ['user_id' => 1]);
        $response = $this->controller->getCategoryAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
        $this->assertEquals('Food', $result['data']['name']);
    }

    public function testGetCategoryNotFound(): void
    {
        $this->service->method('getUserCategories')->willReturn([]);

        $request = Request::create('/api/budgeting/categories/999', 'GET', ['user_id' => 1]);
        $response = $this->controller->getCategoryAction($request, 999);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Category not found', $result['error']);
    }

    public function testCreateCategorySuccess(): void
    {
        $category = $this->createMock(BudgetCategory::class);
        $category->method('getId')->willReturn(1);
        $this->service->method('createCategory')->willReturn($category);

        $body = ['user_id' => 1, 'name' => 'Food', 'type' => 'expense', 'color' => '#FF0000'];
        $request = Request::create('/api/budgeting/categories', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createCategoryAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testCreateCategoryWithDefaults(): void
    {
        $category = new BudgetCategory('New Category', 'expense', '#000000', 1);
        $category->setId(1);
        $this->service->method('createCategory')->willReturn($category);

        $body = ['user_id' => 1, 'name' => 'New Category'];
        $request = Request::create('/api/budgeting/categories', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createCategoryAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals('New Category', $result['data']['name']);
    }

    public function testUpdateCategoryFound(): void
    {
        $category = new BudgetCategory('Old Name', 'expense', '#FF0000', 1);
        $category->setId(1);
        $this->service->method('getUserCategories')->willReturn([$category]);

        $body = ['user_id' => 1, 'name' => 'Updated Name'];
        $request = Request::create('/api/budgeting/categories/1', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->updateCategoryAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Updated Name', $result['data']['name']);
    }

    public function testUpdateCategoryNotFound(): void
    {
        $this->service->method('getUserCategories')->willReturn([]);

        $body = ['user_id' => 1, 'name' => 'Updated'];
        $request = Request::create('/api/budgeting/categories/999', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->updateCategoryAction($request, 999);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Category not found', $result['error']);
    }

    public function testDeleteCategory(): void
    {
        $body = ['user_id' => 1];
        $request = Request::create('/api/budgeting/categories/1', 'DELETE', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->deleteCategoryAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(204, $result['status']);
        $this->assertNull($result['data']);
    }

    // ==================== Budget Tests ====================

    public function testGetBudgets(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budgets = [
            new Budget(1, 1, 1000.00, 'monthly', $startDate),
        ];
        $this->service->method('getUserBudgets')->willReturn($budgets);

        $request = Request::create('/api/budgeting/budgets', 'GET', ['user_id' => 1]);
        $response = $this->controller->getBudgetsAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetActiveBudgets(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budgets = [
            new Budget(1, 1, 1000.00, 'monthly', $startDate),
        ];
        $this->service->method('getActiveBudgets')->willReturn($budgets);

        $request = Request::create('/api/budgeting/budgets/active', 'GET', ['user_id' => 1]);
        $response = $this->controller->getActiveBudgetsAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetBudgetFound(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = new Budget(1, 1, 1000.00, 'monthly', $startDate);
        $budget->setId(1);
        $this->service->method('getUserBudgets')->willReturn([$budget]);

        $request = Request::create('/api/budgeting/budgets/1', 'GET', ['user_id' => 1]);
        $response = $this->controller->getBudgetAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
        $this->assertEquals(1000.00, $result['data']['amount']);
    }

    public function testGetBudgetNotFound(): void
    {
        $this->service->method('getUserBudgets')->willReturn([]);

        $request = Request::create('/api/budgeting/budgets/999', 'GET', ['user_id' => 1]);
        $response = $this->controller->getBudgetAction($request, 999);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Budget not found', $result['error']);
    }

    public function testGetBudgetProgressSuccess(): void
    {
        $progress = [
            'budget' => null,
            'total_spent' => 300.00,
            'remaining' => 700.00,
            'percentage' => 30.0,
            'is_over_budget' => false,
            'expense_count' => 1
        ];
        $this->service->method('getBudgetProgress')->willReturn($progress);

        $request = Request::create('/api/budgeting/budgets/1/progress', 'GET', ['user_id' => 1]);
        $response = $this->controller->getBudgetProgressAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(300.00, $result['data']['total_spent']);
    }

    public function testGetBudgetProgressNotFound(): void
    {
        $this->service->method('getBudgetProgress')
            ->willThrowException(new \InvalidArgumentException('Budget not found'));

        $request = Request::create('/api/budgeting/budgets/999/progress', 'GET', ['user_id' => 1]);
        $response = $this->controller->getBudgetProgressAction($request, 999);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Budget not found', $result['error']);
    }

    public function testCreateBudgetSuccess(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = new Budget(1, 1, 1000.00, 'monthly', $startDate);
        $budget->setId(1);
        $this->service->method('createBudget')->willReturn($budget);

        $body = [
            'user_id' => 1,
            'category_id' => 1,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => '2026-01-01'
        ];
        $request = Request::create('/api/budgeting/budgets', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createBudgetAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
        $this->assertEquals(1000.00, $result['data']['amount']);
    }

    public function testCreateBudgetWithDefaults(): void
    {
        $budget = $this->createMock(Budget::class);
        $budget->method('getId')->willReturn(1);
        $this->service->method('createBudget')->willReturn($budget);

        $body = ['user_id' => 1];
        $request = Request::create('/api/budgeting/budgets', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createBudgetAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testUpdateBudgetFound(): void
    {
        $startDate = new \DateTime('2026-01-01');
        $budget = new Budget(1, 1, 1000.00, 'monthly', $startDate);
        $budget->setId(1);
        $this->service->method('getUserBudgets')->willReturn([$budget]);

        // Mock repository save (returns the budget argument)
        $repo = $this->createMock(BudgetRepository::class);
        $repo->method('save')->willReturnCallback(function($budget) { return $budget; });
        $this->service->method('getBudgetRepository')->willReturn($repo);

        $body = ['user_id' => 1, 'amount' => 2000.00];
        $request = Request::create('/api/budgeting/budgets/1', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->updateBudgetAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2000.00, $result['data']['amount']);
    }

    public function testUpdateBudgetNotFound(): void
    {
        $this->service->method('getUserBudgets')->willReturn([]);

        $body = ['user_id' => 1, 'amount' => 2000.00];
        $request = Request::create('/api/budgeting/budgets/999', 'PUT', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->updateBudgetAction($request, 999);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals('Budget not found', $result['error']);
    }

    public function testDeleteBudget(): void
    {
        $body = ['user_id' => 1];
        $request = Request::create('/api/budgeting/budgets/1', 'DELETE', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->deleteBudgetAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(204, $result['status']);
        $this->assertNull($result['data']);
    }

    // ==================== Expense Tests ====================

    public function testGetExpensesWithoutDateRange(): void
    {
        $expenses = [
            $this->createMock(BudgetExpense::class),
        ];
        $this->service->method('getUserExpenses')->willReturn($expenses);

        $request = Request::create('/api/budgeting/expenses?user_id=1', 'GET');
        $response = $this->controller->getExpensesAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetExpensesWithDateRange(): void
    {
        $expenses = [
            $this->createMock(BudgetExpense::class),
            $this->createMock(BudgetExpense::class),
        ];
        $this->service->method('getUserExpenses')->willReturn($expenses);

        $request = Request::create('/api/budgeting/expenses?user_id=1&start_date=2026-02-01&end_date=2026-02-28', 'GET');
        $response = $this->controller->getExpensesAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetSpendingSummary(): void
    {
        $summary = [
            'total_spending' => 500.00,
            'by_category' => [],
            'expense_count' => 3
        ];
        $this->service->method('getSpendingSummary')->willReturn($summary);

        $request = Request::create('/api/budgeting/expenses/summary?user_id=1', 'GET');
        $response = $this->controller->getSpendingSummaryAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(500.00, $result['data']['total_spending']);
    }

    public function testGetSpendingSummaryWithDates(): void
    {
        $summary = [
            'total_spending' => 300.00,
            'by_category' => [],
            'expense_count' => 2
        ];
        $this->service->method('getSpendingSummary')->willReturn($summary);

        $request = Request::create('/api/budgeting/expenses/summary?user_id=1&start_date=2026-02-01&end_date=2026-02-28', 'GET');
        $response = $this->controller->getSpendingSummaryAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(300.00, $result['data']['total_spending']);
    }

    public function testCreateExpenseSuccess(): void
    {
        $expenseDate = new \DateTime('2026-02-15');
        $expense = new BudgetExpense(1, 1, 1, 50.00, $expenseDate);
        $expense->setId(1);
        $this->service->method('createExpense')->willReturn($expense);

        $body = [
            'user_id' => 1,
            'budget_id' => 1,
            'category_id' => 1,
            'amount' => 50.00,
            'expense_date' => '2026-02-15'
        ];
        $request = Request::create('/api/budgeting/expenses', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createExpenseAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testCreateExpenseWithOptionalFields(): void
    {
        $expense = $this->createMock(BudgetExpense::class);
        $expense->method('getId')->willReturn(1);
        $this->service->method('createExpense')->willReturn($expense);

        $body = ['user_id' => 1];
        $request = Request::create('/api/budgeting/expenses', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->createExpenseAction($request);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testDeleteExpense(): void
    {
        $body = ['user_id' => 1];
        $request = Request::create('/api/budgeting/expenses/1', 'DELETE', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ], json_encode($body));

        $response = $this->controller->deleteExpenseAction($request, 1);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(204, $result['status']);
        $this->assertNull($result['data']);
    }

    // ==================== Route Registration Test ====================

    public function testRegisterRoutesDoesNotThrow(): void
    {
        $router = new class {
            public function get($path, $handler) {}
            public function post($path, $handler) {}
            public function put($path, $handler) {}
            public function delete($path, $handler) {}
        };
        $this->controller->registerRoutes($router);
        $this->assertTrue(true);
    }
}