<?php

declare(strict_types=1);

namespace Tests\Unit\Budgeting\Controller;

use App\Budgeting\Controller\BudgetingController;
use App\Budgeting\Service\BudgetingService;
use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetExpense;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BudgetingControllerTest extends TestCase
{
    private BudgetingService $serviceMock;
    private BudgetingController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(BudgetingService::class);
        $this->controller = new BudgetingController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    // Helper to create category entity
    private function createCategory(array $data = []): BudgetCategory
    {
        $category = new BudgetCategory(
            $data['name'] ?? 'Test Category',
            $data['type'] ?? 'expense',
            $data['color'] ?? '#000000',
            $data['user_id'] ?? 1,
            $data['description'] ?? null
        );
        if (isset($data['id'])) $category->setId((int)$data['id']);
        if (isset($data['parent_id'])) $category->setParentId((int)$data['parent_id']);
        if (array_key_exists('is_default', $data)) $category->setDefault((bool)$data['is_default']);
        return $category;
    }

    // Helper to create budget entity
    private function createBudget(array $data = []): Budget
    {
        $start = $data['start_date'] ?? new \DateTimeImmutable();
        $budget = new Budget(
            $data['user_id'] ?? 1,
            $data['category_id'] ?? 1,
            $data['amount'] ?? 100.0,
            $data['period'] ?? 'monthly',
            $start
        );
        if (isset($data['id'])) $budget->setId((int)$data['id']);
        if (isset($data['note'])) $budget->setNote((string)$data['note']);
        if (array_key_exists('is_active', $data)) $budget->setActive((bool)$data['is_active']);
        return $budget;
    }

    // Helper to create expense entity
    private function createExpense(array $data = []): BudgetExpense
    {
        $date = $data['expense_date'] ?? new \DateTimeImmutable();
        $expense = new BudgetExpense(
            $data['user_id'] ?? 1,
            $data['budget_id'] ?? 1,
            $data['category_id'] ?? 1,
            $data['amount'] ?? 50.0,
            $date
        );
        if (isset($data['id'])) $expense->setId((int)$data['id']);
        if (array_key_exists('description', $data)) $expense->setDescription($data['description'] !== null ? (string)$data['description'] : null);
        if (array_key_exists('merchant', $data)) $expense->setMerchant($data['merchant'] !== null ? (string)$data['merchant'] : null);
        if (array_key_exists('transaction_id', $data)) $expense->setTransactionId($data['transaction_id'] !== null ? (int)$data['transaction_id'] : null);
        return $expense;
    }

    // Helper to create JSON request with body
    private function createJsonRequest(array $data, string $method = 'POST', string $query = ''): Request
    {
        $content = json_encode($data);
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($query) {
            $server['QUERY_STRING'] = $query;
        }
        return Request::create('/api/budgeting', $method, [], [], [], $server, $content);
    }

    // Helper to create GET request with query params
    private function createQueryRequest(array $query = []): Request
    {
        return Request::create('/api/budgeting', 'GET', $query);
    }

    // ==================== Infrastructure tests ====================

    public function testGetJsonData_InvalidJson_Throws(): void
    {
        $request = Request::create('/api/budgeting', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $this->invokePrivate($this->controller, 'getJsonData', [$request]);
    }

    public function testGetJsonData_ValidJson_ReturnsArray(): void
    {
        $request = $this->createJsonRequest(['key' => 'value']);
        $result = $this->invokePrivate($this->controller, 'getJsonData', [$request]);
        $this->assertSame(['key' => 'value'], $result);
    }

    public function testGetJsonData_EmptyJson_ReturnsEmptyArray(): void
    {
        $request = $this->createJsonRequest([]); // {} is valid, getJsonData returns [] if empty? It returns $dataOr400 ?? []
        $result = $this->invokePrivate($this->controller, 'getJsonData', [$request]);
        $this->assertSame([], $result);
    }

    public function testValidateCategoryName_Empty_ReturnsError(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateCategoryName', ['']);
        $this->assertSame('Category name cannot be empty', $result);
    }

    public function testValidateCategoryName_TooLong_ReturnsError(): void
    {
        $long = str_repeat('a', 101);
        $result = $this->invokePrivate($this->controller, 'validateCategoryName', [$long]);
        $this->assertSame('Category name too long', $result);
    }

    public function testValidateCategoryName_Valid_ReturnsNull(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateCategoryName', ['Valid']);
        $this->assertNull($result);
    }

    public function testValidateCategoryType_Invalid_ReturnsError(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateCategoryType', ['invalid']);
        $this->assertSame('Invalid category type', $result);
    }

    public function testValidateCategoryType_Valid_ReturnsNull(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateCategoryType', ['income']);
        $this->assertNull($result);
    }

    public function testValidateColor_Invalid_ReturnsError(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateColor', ['red']);
        $this->assertSame('Invalid color format (expected #RRGGBB)', $result);
    }

    public function testValidateColor_Valid_ReturnsNull(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateColor', ['#1a2b3c']);
        $this->assertNull($result);
    }

    public function testValidatePositiveAmount_Negative_ReturnsError(): void
    {
        $result = $this->invokePrivate($this->controller, 'validatePositiveAmount', [-5.0]);
        $this->assertSame('Amount must be non-negative', $result);
    }

    public function testValidatePositiveAmount_Zero_ReturnsNull(): void
    {
        $result = $this->invokePrivate($this->controller, 'validatePositiveAmount', [0.0]);
        $this->assertNull($result);
    }

    public function testValidateBudgetPeriod_Invalid_ReturnsError(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateBudgetPeriod', ['quarterly']);
        $this->assertSame('Invalid period', $result);
    }

    public function testValidateBudgetPeriod_Valid_ReturnsNull(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateBudgetPeriod', ['daily']);
        $this->assertNull($result);
    }

    public function testValidateDateOrThrow_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        $this->invokePrivate($this->controller, 'validateDateOrThrow', ['not-a-date']);
    }

    public function testValidateDateOrThrow_Valid_ReturnsDateTime(): void
    {
        $result = $this->invokePrivate($this->controller, 'validateDateOrThrow', ['2024-01-15']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    public function testGetUserIdFromQuery_Missing_Throws(): void
    {
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => '']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id is required');
        $this->invokePrivate($this->controller, 'getUserIdFromQuery', [$request]);
    }

    public function testGetUserIdFromQuery_Valid_ReturnsInt(): void
    {
        $request = $this->createQueryRequest(['user_id' => '42']);
        $userId = $this->invokePrivate($this->controller, 'getUserIdFromQuery', [$request]);
        $this->assertSame(42, $userId);
    }

    public function testGetUserIdFromJson_Missing_Throws(): void
    {
        $request = $this->createJsonRequest([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id is required');
        $this->invokePrivate($this->controller, 'getUserIdFromJson', [$request]);
    }

    public function testGetUserIdFromJson_Valid_ReturnsInt(): void
    {
        $request = $this->createJsonRequest(['user_id' => 123]);
        $userId = $this->invokePrivate($this->controller, 'getUserIdFromJson', [$request]);
        $this->assertSame(123, $userId);
    }

    // ==================== Category endpoint tests ====================

    public function testGetCategoriesAction_MissingUserId_Returns400(): void
    {
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getCategoriesAction($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetCategoriesAction_ByType_Returns200(): void
    {
        $cat1 = $this->createCategory(['id' => 1, 'name' => 'Salary', 'type' => 'income']);
        $cat2 = $this->createCategory(['id' => 2, 'name' => 'Food', 'type' => 'expense']);
        $this->serviceMock->expects($this->once())
            ->method('getCategoriesByType')
            ->with(5, 'income')
            ->willReturn([$cat1]);
        $request = $this->createQueryRequest(['user_id' => '5', 'type' => 'income']);
        $response = $this->controller->getCategoriesAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(1, $content['data']);
    }

    public function testGetCategoriesAction_All_Returns200(): void
    {
        $cat1 = $this->createCategory(['id' => 1, 'name' => 'Salary']);
        $cat2 = $this->createCategory(['id' => 2, 'name' => 'Food']);
        $this->serviceMock->expects($this->once())
            ->method('getUserCategories')
            ->with(5)
            ->willReturn([$cat1, $cat2]);
        $request = $this->createQueryRequest(['user_id' => '5']);
        $response = $this->controller->getCategoriesAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content['data']);
    }

    public function testGetCategoryAction_InvalidId_Returns400(): void
    {
        $request = Request::create('/api/budgeting/categories/abc', 'GET');
        // Symfony will not match non-int to {id}, but if called directly we can test with invalid id? Actually the route requires int, so this won't happen. Skip.
        $this->markTestSkipped('Route ensures int id');
    }

    public function testGetCategoryAction_NotFound_Returns404(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getUserCategories')
            ->with(5)
            ->willReturn([]);
        $request = $this->createQueryRequest(['user_id' => '5']);
        $response = $this->controller->getCategoryAction($request, 999);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetCategoryAction_Success_Returns200(): void
    {
        $cat = $this->createCategory(['id' => 2, 'name' => 'Food', 'type' => 'expense']);
        $this->serviceMock->expects($this->once())
            ->method('getUserCategories')
            ->with(5)
            ->willReturn([$cat]);
        $request = $this->createQueryRequest(['user_id' => '5']);
        $response = $this->controller->getCategoryAction($request, 2);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('Food', $content['data']['name']);
    }

    public function testCreateCategoryAction_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/budgeting/categories', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createCategoryAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateCategoryAction_MissingName_Throws400(): void
    {
        $userId = 5;
        $request = $this->createJsonRequest(['user_id' => $userId, 'type' => 'expense']);
        $response = $this->controller->createCategoryAction($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('name required', $content['error']);
    }

    public function testCreateCategoryAction_InvalidColor_Throws400(): void
    {
        $userId = 5;
        $request = $this->createJsonRequest(['user_id' => $userId, 'name' => 'Cat', 'color' => 'red']);
        $response = $this->controller->createCategoryAction($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid color format', $content['error']);
    }

    public function testCreateCategoryAction_InvalidType_Throws400(): void
    {
        $request = $this->createJsonRequest(['name' => 'Cat', 'type' => 'invalid']);
        $response = $this->controller->createCategoryAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateCategoryAction_Success_Returns201(): void
    {
        $userId = 5;
        $savedCategory = $this->createCategory(['id' => 10, 'name' => 'New Cat', 'type' => 'income', 'color' => '#123456']);
        $this->serviceMock->expects($this->once())
            ->method('createCategory')
            ->with('New Cat', 'income', '#123456', $userId, null)
            ->willReturn($savedCategory);
        $request = $this->createJsonRequest(['name' => 'New Cat', 'type' => 'income', 'color' => '#123456'], 'POST', '');
        // Need to include user_id in JSON body
        $request = $this->createJsonRequest(['user_id' => $userId, 'name' => 'New Cat', 'type' => 'income', 'color' => '#123456']);
        $response = $this->controller->createCategoryAction($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(10, $content['data']['id']);
    }

    public function testUpdateCategoryAction_NotFound_Returns404(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('getUserCategories')
            ->with($userId)
            ->willReturn([]);
        $request = $this->createJsonRequest(['user_id' => $userId, 'name' => 'Updated']);
        $response = $this->controller->updateCategoryAction($request, 99);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateCategoryAction_InvalidJson_Returns400(): void
    {
        $userId = 5;
        $cat = $this->createCategory(['id' => 1, 'name' => 'Old', 'user_id' => $userId]);
        $this->serviceMock->method('getUserCategories')->with($userId)->willReturn([$cat]);
        $request = Request::create('/api/budgeting/categories/1', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateCategoryAction($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateCategoryAction_UpdateName_Returns200(): void
    {
        $userId = 5;
        $cat = $this->createCategory(['id' => 1, 'name' => 'Old', 'user_id' => $userId]);
        $this->serviceMock->expects($this->once())
            ->method('getUserCategories')
            ->with($userId)
            ->willReturn([$cat]);
        $this->serviceMock->expects($this->once())
            ->method('saveCategory')
            ->with($this->callback(function($c) use ($userId) {
                return $c->getId() === 1 && $c->getName() === 'New Name';
            }));
        $request = $this->createJsonRequest(['user_id' => $userId, 'name' => 'New Name']);
        $response = $this->controller->updateCategoryAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateCategoryAction_UpdateColor_Returns200(): void
    {
        $userId = 5;
        $cat = $this->createCategory(['id' => 1, 'name' => 'Old', 'color' => '#000000', 'user_id' => $userId]);
        $this->serviceMock->method('getUserCategories')->with($userId)->willReturn([$cat]);
        $this->serviceMock->expects($this->once())
            ->method('saveCategory')
            ->with($this->callback(fn($c) => $c->getColor() === '#FFFFFF'));
        $request = $this->createJsonRequest(['user_id' => $userId, 'color' => '#FFFFFF']);
        $response = $this->controller->updateCategoryAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateCategoryAction_UpdateParentAndDefault_Returns200(): void
    {
        $userId = 5;
        $cat = $this->createCategory(['id' => 1, 'name' => 'Child', 'parent_id' => null, 'is_default' => false, 'user_id' => $userId]);
        $this->serviceMock->method('getUserCategories')->with($userId)->willReturn([$cat]);
        $this->serviceMock->expects($this->once())
            ->method('saveCategory')
            ->with($this->callback(fn($c) => $c->getParentId() === 2 && $c->isDefault() === true));
        $request = $this->createJsonRequest(['user_id' => $userId, 'parent_id' => 2, 'is_default' => true]);
        $response = $this->controller->updateCategoryAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeleteCategoryAction_NotFound_Returns404(): void
    {
        $userId = 5;
        // Simulate service throwing NotFoundHttpException when deleting non-existent category
        $this->serviceMock->expects($this->once())
            ->method('deleteCategory')
            ->with(1, $userId)
            ->willThrowException(new NotFoundHttpException('Category not found'));
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteCategoryAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteCategoryAction_Success_Returns204(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('deleteCategory')
            ->with(1, $userId);
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteCategoryAction($request, 1);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== Budget endpoint tests ====================

    public function testGetBudgetsAction_MissingUserId_Returns400(): void
    {
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getBudgetsAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetBudgetsAction_Success_Returns200(): void
    {
        $userId = 5;
        $budget1 = $this->createBudget(['id' => 1, 'amount' => 200]);
        $budget2 = $this->createBudget(['id' => 2, 'amount' => 300]);
        $this->serviceMock->expects($this->once())
            ->method('getUserBudgets')
            ->with($userId)
            ->willReturn([$budget1, $budget2]);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getBudgetsAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content['data']);
    }

    public function testGetActiveBudgetsAction_Success_Returns200(): void
    {
        $userId = 5;
        $budget = $this->createBudget(['id' => 1, 'is_active' => true]);
        $this->serviceMock->expects($this->once())
            ->method('getActiveBudgets')
            ->with($userId)
            ->willReturn([$budget]);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getActiveBudgetsAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content['data']);
    }

    public function testGetBudgetAction_NotFound_Returns404(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('getUserBudgets')
            ->with($userId)
            ->willReturn([]);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getBudgetAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetBudgetAction_Success_Returns200(): void
    {
        $userId = 5;
        $budget = $this->createBudget(['id' => 1, 'amount' => 500]);
        $this->serviceMock->expects($this->once())
            ->method('getUserBudgets')
            ->with($userId)
            ->willReturn([$budget]);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getBudgetAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(500, $content['data']['amount']);
    }

    public function testGetBudgetProgressAction_NotFound_Returns404(): void
    {
        $userId = 5; // though progress doesn't need userId for authorization here, but route calls getUserIdFromQuery first
        $this->serviceMock->expects($this->once())
            ->method('getBudgetProgress')
            ->with(1)
            ->willThrowException(new InvalidArgumentException('Not found'));
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getBudgetProgressAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetBudgetProgressAction_Success_Returns200(): void
    {
        $progress = ['budget_id' => 1, 'spent' => 50.0, 'remaining' => 50.0];
        $this->serviceMock->expects($this->once())
            ->method('getBudgetProgress')
            ->with(1)
            ->willReturn($progress);
        $request = $this->createQueryRequest(['user_id' => '5']);
        $response = $this->controller->getBudgetProgressAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(50.0, $content['data']['spent']); // float comparison
    }

    public function testCreateBudgetAction_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/budgeting/budgets', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createBudgetAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateBudgetAction_MissingUserId_Throws400(): void
    {
        $request = $this->createJsonRequest(['amount' => 100, 'period' => 'monthly']);
        $response = $this->controller->createBudgetAction($request);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('user_id is required', $content['error']);
    }

    public function testCreateBudgetAction_InvalidAmount_Throws400(): void
    {
        $request = $this->createJsonRequest(['user_id' => 5, 'amount' => -10]);
        $response = $this->controller->createBudgetAction($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('non-negative', $response->getContent());
    }

    public function testCreateBudgetAction_InvalidPeriod_Throws400(): void
    {
        $request = $this->createJsonRequest(['user_id' => 5, 'amount' => 100, 'period' => 'quarterly']);
        $response = $this->controller->createBudgetAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateBudgetAction_Success_Returns201(): void
    {
        $userId = 5;
        $categoryId = 2;
        $now = new \DateTimeImmutable();
        $budget = $this->createBudget([
            'id' => 10,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => 200.0,
            'period' => 'weekly',
            'start_date' => $now
        ]);
        $this->serviceMock->expects($this->once())
            ->method('createBudget')
            ->with(
                $userId,
                $categoryId,
                200.0,
                'weekly',
                $this->isInstanceOf(\DateTimeImmutable::class),
                null
            )
            ->willReturn($budget);
        $request = $this->createJsonRequest([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => 200.0,
            'period' => 'weekly',
            'start_date' => $now->format('c')
        ]);
        $response = $this->controller->createBudgetAction($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(10, $content['data']['id']);
    }

    public function testUpdateBudgetAction_NotFound_Returns404(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('getUserBudgets')
            ->with($userId)
            ->willReturn([]);
        $request = $this->createJsonRequest(['user_id' => $userId, 'amount' => 150]);
        $response = $this->controller->updateBudgetAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateBudgetAction_InvalidAmount_Throws400(): void
    {
        $userId = 5;
        $budget = $this->createBudget(['id' => 1, 'user_id' => $userId, 'amount' => 100]);
        $this->serviceMock->method('getUserBudgets')->with($userId)->willReturn([$budget]);
        $request = $this->createJsonRequest(['user_id' => $userId, 'amount' => -50]);
        $response = $this->controller->updateBudgetAction($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateBudgetAction_InvalidPeriod_Throws400(): void
    {
        $userId = 5;
        $budget = $this->createBudget(['id' => 1, 'user_id' => $userId, 'amount' => 100]);
        $this->serviceMock->method('getUserBudgets')->with($userId)->willReturn([$budget]);
        $request = $this->createJsonRequest(['user_id' => $userId, 'period' => 'century']);
        $response = $this->controller->updateBudgetAction($request, 1);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateBudgetAction_Success_Returns200(): void
    {
        $userId = 5;
        $budget = $this->createBudget(['id' => 1, 'user_id' => $userId, 'amount' => 100, 'period' => 'monthly', 'note' => null, 'is_active' => true]);
        $this->serviceMock->expects($this->once())
            ->method('getUserBudgets')
            ->with($userId)
            ->willReturn([$budget]);
        // We need to capture repository save call: controller does $this->service->getBudgetRepository()->save($budget)
        $repoMock = $this->createMock(\App\Budgeting\Repository\BudgetRepositoryInterface::class);
        $repoMock->expects($this->once())->method('save')->with($this->callback(function($b) use ($userId) {
            return $b->getId() === 1 && $b->getAmount() === 150.0;
        }));
        $this->serviceMock->method('getBudgetRepository')->willReturn($repoMock);
        $request = $this->createJsonRequest(['user_id' => $userId, 'amount' => 150.0]);
        $response = $this->controller->updateBudgetAction($request, 1);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeleteBudgetAction_NotFound_Returns404(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('deleteBudget')
            ->with(1, $userId)
            ->willThrowException(new NotFoundHttpException('Budget not found'));
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteBudgetAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteBudgetAction_Success_Returns204(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('deleteBudget')
            ->with(1, $userId);
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteBudgetAction($request, 1);
        $this->assertSame(204, $response->getStatusCode());
    }

    // ==================== Expense endpoint tests ====================

    public function testGetExpensesAction_MissingUserId_Returns400(): void
    {
        $request = $this->createQueryRequest([]);
        $response = $this->controller->getExpensesAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetExpensesAction_InvalidDate_Returns400(): void
    {
        $request = $this->createQueryRequest(['user_id' => '5', 'start_date' => 'not-date']);
        $response = $this->controller->getExpensesAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetExpensesAction_Success_Returns200(): void
    {
        $userId = 5;
        $exp1 = $this->createExpense(['id' => 1, 'amount' => 30]);
        $exp2 = $this->createExpense(['id' => 2, 'amount' => 70]);
        $this->serviceMock->expects($this->once())
            ->method('getUserExpenses')
            ->with($userId, null, null)
            ->willReturn([$exp1, $exp2]);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getExpensesAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content['data']);
    }

    public function testGetSpendingSummaryAction_Success_Returns200(): void
    {
        $userId = 5;
        $summary = ['total' => 500.0, 'by_category' => []];
        $this->serviceMock->expects($this->once())
            ->method('getSpendingSummary')
            ->with($userId, $this->anything(), $this->anything())
            ->willReturn($summary);
        $request = $this->createQueryRequest(['user_id' => $userId]);
        $response = $this->controller->getSpendingSummaryAction($request);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(500.0, $content['data']['total']);
    }

    public function testCreateExpenseAction_InvalidJson_Returns400(): void
    {
        $request = Request::create('/api/budgeting/expenses', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->createExpenseAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateExpenseAction_MissingUserId_Throws400(): void
    {
        $request = $this->createJsonRequest(['amount' => 50]);
        $response = $this->controller->createExpenseAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateExpenseAction_InvalidAmount_Throws400(): void
    {
        $request = $this->createJsonRequest(['user_id' => 5, 'budget_id' => 1, 'category_id' => 1, 'amount' => -10]);
        $response = $this->controller->createExpenseAction($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateExpenseAction_Success_Returns201(): void
    {
        $userId = 5;
        $budgetId = 1;
        $categoryId = 1;
        $amount = 75.0;
        $expense = $this->createExpense([
            'id' => 10,
            'user_id' => $userId,
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
            'amount' => $amount
        ]);
        $this->serviceMock->expects($this->once())
            ->method('createExpense')
            ->with($userId, $budgetId, $categoryId, $amount, $this->anything(), null, null, null)
            ->willReturn($expense);
        $request = $this->createJsonRequest([
            'user_id' => $userId,
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
            'amount' => $amount
        ]);
        $response = $this->controller->createExpenseAction($request);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame(10, $content['data']['id']);
    }

    public function testDeleteExpenseAction_NotFound_Returns404(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('deleteExpense')
            ->with(1, $userId)
            ->willThrowException(new NotFoundHttpException('Expense not found'));
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteExpenseAction($request, 1);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteExpenseAction_Success_Returns204(): void
    {
        $userId = 5;
        $this->serviceMock->expects($this->once())
            ->method('deleteExpense')
            ->with(1, $userId);
        $request = $this->createJsonRequest(['user_id' => $userId]);
        $response = $this->controller->deleteExpenseAction($request, 1);
        $this->assertSame(204, $response->getStatusCode());
    }
}
