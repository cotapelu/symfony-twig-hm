<?php

declare(strict_types=1);

namespace App\Budgeting\Controller;

use App\Budgeting\Entity\BudgetCategory;
use App\Budgeting\Entity\Budget;
use App\Budgeting\Entity\BudgetExpense;
use App\Budgeting\Service\BudgetingService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/budgeting')]
class BudgetingController extends BaseController
{
    private const MAX_NAME_LENGTH = 100;
    private const MAX_COLOR_LENGTH = 7;
    private const MAX_DESCRIPTION_LENGTH = 500;
    private const ALLOWED_TYPES = ['expense', 'income', 'savings', 'investment'];
    private const ALLOWED_PERIODS = ['daily', 'weekly', 'monthly', 'yearly'];

    public function __construct(private BudgetingService $service)
    {
        parent::__construct();
    }

    public function registerRoutes($router): void
    {
        // Routes are defined via Symfony attributes.
    }

    // ==================== Infrastructure ====================
    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            throw new InvalidArgumentException('Invalid JSON');
        }
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['status' => 400, 'error' => $e->getMessage()], 400);
        } catch (NotFoundHttpException $e) {
            return $this->json(['status' => 404, 'error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            error_log('BudgetingController error: ' . $e->getMessage());
            return $this->json(['status' => 500, 'error' => 'Internal server error'], 500);
        }
    }

    // ==================== Helpers ====================
    private function getUserIdFromQuery(Request $request): int
    {
        $userId = $request->query->get('user_id');
        if (!$userId) {
            throw new InvalidArgumentException('user_id is required');
        }
        return (int)$userId;
    }

    private function getUserIdFromJson(Request $request): int
    {
        $data = $this->getJsonData($request);
        if (!isset($data['user_id'])) {
            throw new InvalidArgumentException('user_id is required');
        }
        return (int)$data['user_id'];
    }

    private function validateCategoryName(string $name): ?string
    {
        if (trim($name) === '') {
            return 'Category name cannot be empty';
        }
        if (strlen($name) > self::MAX_NAME_LENGTH) {
            return 'Category name too long';
        }
        return null;
    }

    private function validateCategoryType(string $type): ?string
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return 'Invalid category type';
        }
        return null;
    }

    private function validateColor(string $color): ?string
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return 'Invalid color format (expected #RRGGBB)';
        }
        return null;
    }

    private function validatePositiveAmount(float $amount): ?string
    {
        if ($amount < 0) {
            return 'Amount must be non-negative';
        }
        return null;
    }

    private function validateBudgetPeriod(string $period): ?string
    {
        if (!in_array($period, self::ALLOWED_PERIODS, true)) {
            return 'Invalid period';
        }
        return null;
    }

    private function validateDateOrThrow(string $dateStr): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($dateStr);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid date format: $dateStr");
        }
    }

    private function findCategoryOr404(int $userId, int $id): BudgetCategory
    {
        $categories = $this->service->getUserCategories($userId);
        foreach ($categories as $category) {
            if ($category->getId() === $id) {
                return $category;
            }
        }
        throw new NotFoundHttpException('Category not found');
    }

    private function findBudgetOr404(int $userId, int $id): Budget
    {
        $budgets = $this->service->getUserBudgets($userId);
        foreach ($budgets as $budget) {
            if ($budget->getId() === $id) {
                return $budget;
            }
        }
        throw new NotFoundHttpException('Budget not found');
    }

    // ==================== Serialization ====================
    private function serializeCategory(BudgetCategory $c): array
    {
        $createdAt = $c->getCreatedAt();
        $updatedAt = $c->getUpdatedAt();
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'type' => $c->getType(),
            'description' => $c->getDescription(),
            'color' => $c->getColor(),
            'user_id' => $c->getUserId(),
            'parent_id' => $c->getParentId(),
            'is_default' => $c->isDefault(),
            'created_at' => $createdAt ? $createdAt->format('c') : null,
            'updated_at' => $updatedAt ? $updatedAt->format('c') : null,
        ];
    }

    private function serializeBudget(Budget $b): array
    {
        $startDate = $b->getStartDate();
        $endDate = $b->getEndDate();
        $createdAt = $b->getCreatedAt();
        $updatedAt = $b->getUpdatedAt();
        return [
            'id' => $b->getId(),
            'user_id' => $b->getUserId(),
            'category_id' => $b->getCategoryId(),
            'amount' => $b->getAmount(),
            'period' => $b->getPeriod(),
            'start_date' => $startDate ? $startDate->format('c') : null,
            'end_date' => $endDate ? $endDate->format('c') : null,
            'note' => $b->getNote(),
            'is_active' => $b->isActive(),
            'created_at' => $createdAt ? $createdAt->format('c') : null,
            'updated_at' => $updatedAt ? $updatedAt->format('c') : null,
        ];
    }

    private function serializeExpense(BudgetExpense $e): array
    {
        $expenseDate = $e->getExpenseDate();
        $createdAt = $e->getCreatedAt();
        $updatedAt = $e->getUpdatedAt();
        return [
            'id' => $e->getId(),
            'user_id' => $e->getUserId(),
            'budget_id' => $e->getBudgetId(),
            'category_id' => $e->getCategoryId(),
            'amount' => $e->getAmount(),
            'description' => $e->getDescription(),
            'merchant' => $e->getMerchant(),
            'transaction_id' => $e->getTransactionId(),
            'expense_date' => $expenseDate ? $expenseDate->format('c') : null,
            'created_at' => $createdAt ? $createdAt->format('c') : null,
            'updated_at' => $updatedAt ? $updatedAt->format('c') : null,
        ];
    }

    // ==================== Category Wrappers ====================
    #[Route('/categories', name: 'budgeting_categories', methods: ['GET'])]
    public function getCategoriesAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getCategories($request));
    }

    #[Route('/categories/{id}', name: 'budgeting_category', methods: ['GET'])]
    public function getCategoryAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getCategory($request, $id));
    }

    #[Route('/categories', name: 'budgeting_create_category', methods: ['POST'])]
    public function createCategoryAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createCategory($request));
    }

    #[Route('/categories/{id}', name: 'budgeting_update_category', methods: ['PUT'])]
    public function updateCategoryAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->updateCategory($request, $id));
    }

    #[Route('/categories/{id}', name: 'budgeting_delete_category', methods: ['DELETE'])]
    public function deleteCategoryAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteCategory($request, $id));
    }

    // ==================== Budget Wrappers ====================
    #[Route('/budgets', name: 'budgeting_budgets', methods: ['GET'])]
    public function getBudgetsAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getBudgets($request));
    }

    #[Route('/budgets/active', name: 'budgeting_active_budgets', methods: ['GET'])]
    public function getActiveBudgetsAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getActiveBudgets($request));
    }

    #[Route('/budgets/{id}', name: 'budgeting_budget', methods: ['GET'])]
    public function getBudgetAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getBudget($request, $id));
    }

    #[Route('/budgets/{id}/progress', name: 'budgeting_budget_progress', methods: ['GET'])]
    public function getBudgetProgressAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getBudgetProgress($request, $id));
    }

    #[Route('/budgets', name: 'budgeting_create_budget', methods: ['POST'])]
    public function createBudgetAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createBudget($request));
    }

    #[Route('/budgets/{id}', name: 'budgeting_update_budget', methods: ['PUT'])]
    public function updateBudgetAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->updateBudget($request, $id));
    }

    #[Route('/budgets/{id}', name: 'budgeting_delete_budget', methods: ['DELETE'])]
    public function deleteBudgetAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteBudget($request, $id));
    }

    // ==================== Expense Wrappers ====================
    #[Route('/expenses', name: 'budgeting_expenses', methods: ['GET'])]
    public function getExpensesAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getExpenses($request));
    }

    #[Route('/expenses/summary', name: 'budgeting_expenses_summary', methods: ['GET'])]
    public function getSpendingSummaryAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getSpendingSummary($request));
    }

    #[Route('/expenses', name: 'budgeting_create_expense', methods: ['POST'])]
    public function createExpenseAction(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createExpense($request));
    }

    #[Route('/expenses/{id}', name: 'budgeting_delete_expense', methods: ['DELETE'])]
    public function deleteExpenseAction(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteExpense($request, $id));
    }

    // ==================== Category Actions ====================
    private function getCategories(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $type = $request->query->get('type');
        if ($type) {
            $categories = $this->service->getCategoriesByType($userId, (string)$type);
        } else {
            $categories = $this->service->getUserCategories($userId);
        }
        $data = array_map([$this, 'serializeCategory'], $categories);
        return $this->json(['status' => 200, 'data' => $data]);
    }

    private function getCategory(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $category = $this->findCategoryOr404($userId, $id);
        return $this->json(['status' => 200, 'data' => $this->serializeCategory($category)]);
    }

    private function validateCreateCategoryData(array $data): array
    {
        if (empty($data['name'])) throw new InvalidArgumentException('name required');
        $name = (string)$data['name'];
        $type = (string)($data['type'] ?? 'expense');
        $color = (string)($data['color'] ?? '#000000');
        $description = isset($data['description']) ? (string)$data['description'] : null;
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
        $isDefault = (bool)($data['is_default'] ?? false);

        $error = $this->validateCategoryName($name); if ($error) throw new InvalidArgumentException($error);
        $error = $this->validateCategoryType($type); if ($error) throw new InvalidArgumentException($error);
        $error = $this->validateColor($color); if ($error) throw new InvalidArgumentException($error);
        if ($description && strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            throw new InvalidArgumentException('Description too long');
        }
        return ['name'=>$name,'type'=>$type,'color'=>$color,'description'=>$description,'parentId'=>$parentId,'isDefault'=>$isDefault];
    }

    private function createCategory(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $data = $this->getJsonData($request);
        $validated = $this->validateCreateCategoryData($data);
        $name = $validated['name'];
        $type = $validated['type'];
        $color = $validated['color'];
        $description = $validated['description'];
        $parentId = $validated['parentId'];
        $isDefault = $validated['isDefault'];

        $category = $this->service->createCategory($name, $type, $color, $userId, $description);
        if ($parentId !== null) $category->setParentId($parentId);
        if ($isDefault) $category->setDefault($isDefault);

        return $this->json(['status'=>201, 'data'=>$this->serializeCategory($category)], 201);
    }

    private function applyCategoryUpdates(BudgetCategory $category, array $data): void
    {
        $this->applyCategoryNameUpdate($category, $data);
        $this->applyCategoryTypeUpdate($category, $data);
        $this->applyCategoryDescriptionUpdate($category, $data);
        $this->applyCategoryColorUpdate($category, $data);
        $this->applyCategoryParentIdUpdate($category, $data);
        $this->applyCategoryDefaultUpdate($category, $data);
    }

    private function applyCategoryNameUpdate(BudgetCategory $c, array $data): void
    {
        if (isset($data['name'])) {
            $name = (string)$data['name'];
            $error = $this->validateCategoryName($name);
            if ($error) throw new InvalidArgumentException($error);
            $c->setName($name);
        }
    }

    private function applyCategoryTypeUpdate(BudgetCategory $c, array $data): void
    {
        if (isset($data['type'])) {
            $type = (string)$data['type'];
            $error = $this->validateCategoryType($type);
            if ($error) throw new InvalidArgumentException($error);
            $c->setType($type);
        }
    }

    private function applyCategoryDescriptionUpdate(BudgetCategory $c, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $desc = $data['description'];
            if ($desc !== null && strlen($desc) > self::MAX_DESCRIPTION_LENGTH) {
                throw new InvalidArgumentException('Description too long');
            }
            $c->setDescription($desc);
        }
    }

    private function applyCategoryColorUpdate(BudgetCategory $c, array $data): void
    {
        if (isset($data['color'])) {
            $color = (string)$data['color'];
            $error = $this->validateColor($color);
            if ($error) throw new InvalidArgumentException($error);
            $c->setColor($color);
        }
    }

    private function applyCategoryParentIdUpdate(BudgetCategory $c, array $data): void
    {
        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] !== null ? (int)$data['parent_id'] : null;
            $c->setParentId($parentId);
        }
    }

    private function applyCategoryDefaultUpdate(BudgetCategory $c, array $data): void
    {
        if (array_key_exists('is_default', $data)) {
            $c->setDefault((bool)$data['is_default']);
        }
    }

    private function updateCategory(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $data = $this->getJsonData($request);
        $category = $this->findCategoryOr404($userId, $id);
        $this->applyCategoryUpdates($category, $data);
        $this->service->saveCategory($category);
        return $this->json(['status'=>200, 'data'=>$this->serializeCategory($category)]);
    }

    private function deleteCategory(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $this->service->deleteCategory($id, $userId);
        return $this->json(['status' => 204], 204);
    }

    // ==================== Budget Actions ====================
    private function getBudgets(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $budgets = $this->service->getUserBudgets($userId);
        $data = array_map([$this, 'serializeBudget'], $budgets);
        return $this->json(['status' => 200, 'data' => $data]);
    }

    private function getActiveBudgets(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $budgets = $this->service->getActiveBudgets($userId);
        $data = array_map([$this, 'serializeBudget'], $budgets);
        return $this->json(['status' => 200, 'data' => $data]);
    }

    private function getBudget(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $budget = $this->findBudgetOr404($userId, $id);
        return $this->json(['status' => 200, 'data' => $this->serializeBudget($budget)]);
    }

    private function getBudgetProgress(Request $request, int $id): JsonResponse
    {
        $this->getUserIdFromQuery($request);
        try {
            $progress = $this->service->getBudgetProgress($id);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
        return $this->json(['status' => 200, 'data' => $progress]);
    }

    private function createBudget(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $data = $this->getJsonData($request);

        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $period = isset($data['period']) ? (string)$data['period'] : 'monthly';
        $startDate = isset($data['start_date']) ? $this->validateDateOrThrow($data['start_date']) : new DateTimeImmutable();
        $note = isset($data['note']) && $data['note'] !== '' ? (string)$data['note'] : null;

        $error = $this->validatePositiveAmount($amount); if ($error) throw new InvalidArgumentException($error);
        $error = $this->validateBudgetPeriod($period); if ($error) throw new InvalidArgumentException($error);

        $budget = $this->service->createBudget($userId, $categoryId, $amount, $period, $startDate, $note);
        return $this->json(['status' => 201, 'data' => $this->serializeBudget($budget)], 201);
    }

    private function updateBudget(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $data = $this->getJsonData($request);
        $budget = $this->findBudgetOr404($userId, $id);

        if (isset($data['amount'])) {
            $amount = (float)$data['amount'];
            $error = $this->validatePositiveAmount($amount); if ($error) throw new InvalidArgumentException($error);
            $budget->setAmount($amount);
        }
        if (isset($data['period'])) {
            $period = (string)$data['period'];
            $error = $this->validateBudgetPeriod($period); if ($error) throw new InvalidArgumentException($error);
            $budget->setPeriod($period);
        }
        if (array_key_exists('note', $data)) {
            $note = $data['note'] !== null ? (string)$data['note'] : null;
            $budget->setNote($note);
        }
        if (array_key_exists('is_active', $data)) {
            $budget->setActive((bool)$data['is_active']);
        }

        $this->service->getBudgetRepository()->save($budget);
        return $this->json(['status' => 200, 'data' => $this->serializeBudget($budget)]);
    }

    private function deleteBudget(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $this->service->deleteBudget($id, $userId);
        return $this->json(['status' => 204], 204);
    }

    // ==================== Expense Actions ====================
    private function getExpenses(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $start = null;
        $end = null;

        $startStr = $request->query->get('start_date');
        if ($startStr) {
            $start = $this->validateDateOrThrow($startStr);
        }

        $endStr = $request->query->get('end_date');
        if ($endStr) {
            $end = $this->validateDateOrThrow($endStr);
        }

        $expenses = $this->service->getUserExpenses($userId, $start, $end);
        $data = array_map([$this, 'serializeExpense'], $expenses);
        return $this->json(['status' => 200, 'data' => $data]);
    }

    private function getSpendingSummary(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromQuery($request);
        $start = null;
        $end = null;

        $startStr = $request->query->get('start_date');
        if ($startStr) {
            $start = $this->validateDateOrThrow($startStr);
        }

        $endStr = $request->query->get('end_date');
        if ($endStr) {
            $end = $this->validateDateOrThrow($endStr);
        }

        if (!$start) {
            $now = new DateTimeImmutable();
            $start = $now->modify('first day of this month')->setTime(0, 0, 0);
        }
        if (!$end) {
            $end = new DateTimeImmutable();
        }

        $summary = $this->service->getSpendingSummary($userId, $start, $end);
        return $this->json(['status' => 200, 'data' => $summary]);
    }

    private function getExpenseData(Request $request): array
    {
        $data = $this->getJsonData($request);
        $budgetId = isset($data['budget_id']) ? (int)$data['budget_id'] : 0;
        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $expenseDate = isset($data['expense_date']) ? $this->validateDateOrThrow($data['expense_date']) : new DateTimeImmutable();
        $description = isset($data['description']) ? (string)$data['description'] : null;
        $merchant = isset($data['merchant']) ? (string)$data['merchant'] : null;
        $transactionId = isset($data['transaction_id']) ? ($data['transaction_id'] !== null ? (int)$data['transaction_id'] : null) : null;

        $error = $this->validatePositiveAmount($amount);
        if ($error) throw new InvalidArgumentException($error);

        return [
            'budgetId' => $budgetId,
            'categoryId' => $categoryId,
            'amount' => $amount,
            'expenseDate' => $expenseDate,
            'description' => $description,
            'merchant' => $merchant,
            'transactionId' => $transactionId
        ];
    }

    private function createExpense(Request $request): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $exp = $this->getExpenseData($request);
        $expense = $this->service->createExpense(
            $userId,
            $exp['budgetId'],
            $exp['categoryId'],
            $exp['amount'],
            $exp['expenseDate'],
            $exp['description'],
            $exp['merchant'],
            $exp['transactionId']
        );
        return $this->json(['status'=>201, 'data'=>$this->serializeExpense($expense)], 201);
    }

    private function deleteExpense(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUserIdFromJson($request);
        $this->service->deleteExpense($id, $userId);
        return $this->json(['status' => 204], 204);
    }
}