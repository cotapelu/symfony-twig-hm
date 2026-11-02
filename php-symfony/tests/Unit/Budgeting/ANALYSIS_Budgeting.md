# Pre-Analysis: Budgeting Module

**Date**: 2026-02-18
**Module**: Budgeting (PHP Symfony)
**Files Analyzed**: 7

---

## A) EXPORT MAP

### Entities

#### Budget.php
- **Constructor**: `__construct(int $userId, int $categoryId, float $amount, string $period, \DateTime $startDate)`
- **Getters**: 
  - `getId(): ?int`
  - `getUserId(): int`
  - `getCategoryId(): int`
  - `getAmount(): float`
  - `getPeriod(): string`
  - `getStartDate(): \DateTime`
  - `getEndDate(): \DateTime`
  - `getNote(): ?string`
  - `isActive(): bool`
- **Setters**:
  - `setAmount(float $amount): self`
  - `setPeriod(string $period): self`
  - `setNote(?string $note): self`
  - `setActive(bool $isActive): self`

#### BudgetCategory.php
- **Constructor**: `__construct(string $name, string $type, string $color, int $userId)`
- **Getters**:
  - `getId(): ?int`
  - `getName(): string`
  - `getType(): string`
  - `getDescription(): ?string`
  - `getColor(): string`
  - `getUserId(): int`
  - `getParentId(): ?int`
  - `isDefault(): bool`
- **Setters**:
  - `setName(string $name): self`
  - `setType(string $type): self`
  - `setDescription(?string $description): self`
  - `setColor(string $color): self`
  - `setParentId(?int $parentId): self`
  - `setDefault(bool $isDefault): self`

#### BudgetExpense.php
- **Constructor**: `__construct(int $userId, int $budgetId, int $categoryId, float $amount, \DateTime $expenseDate)`
- **Getters**:
  - `getId(): ?int`
  - `getUserId(): int`
  - `getBudgetId(): int`
  - `getCategoryId(): int`
  - `getAmount(): float`
  - `getDescription(): ?string`
  - `getMerchant(): ?string`
  - `getTransactionId(): ?int`
  - `getExpenseDate(): \DateTime`
- **Setters**:
  - `setDescription(?string $description): self`
  - `setMerchant(?string $merchant): self`
  - `setTransactionId(?int $transactionId): self`

### Service: BudgetingService.php

**Constructor**: `__construct(BudgetCategoryRepositoryInterface, BudgetRepositoryInterface, BudgetExpenseRepositoryInterface)`

**Category Methods**:
- `createCategory(string $name, string $type, string $color, int $userId, ?string $description = null): BudgetCategory`
- `getUserCategories(int $userId): array`
- `getCategoriesByType(int $userId, string $type): array`

**Budget Methods**:
- `createBudget(int $userId, int $categoryId, float $amount, string $period, \DateTime $startDate, ?string $note = null): Budget`
- `getUserBudgets(int $userId): array`
- `getActiveBudgets(int $userId): array`
- `getBudgetProgress(int $budgetId): array` - **THROWS**: `\InvalidArgumentException` if budget not found

**Expense Methods**:
- `createExpense(int $userId, int $budgetId, int $categoryId, float $amount, \DateTime $expenseDate, ?string $description = null, ?string $merchant = null): BudgetExpense`
- `getUserExpenses(int $userId, ?\DateTime $start = null, ?\DateTime $end = null): array`
- `getSpendingSummary(int $userId, \DateTime $start, \DateTime $end): array`

### Repository Interfaces

#### BudgetRepositoryInterface
- `findById(int $id): ?Budget`
- `findByUserId(int $userId): array`
- `findActiveByUserId(int $userId): array`
- `findByCategoryId(int $categoryId): array`
- `save(Budget $budget): Budget`
- `delete(int $id): bool`

#### BudgetCategoryRepositoryInterface
- `findById(int $id): ?BudgetCategory`
- `findByUserId(int $userId): array`
- `findByType(int $userId, string $type): array`
- `save(BudgetCategory $category): BudgetCategory`
- `delete(int $id): bool`
- `findDefaults(): array`

#### BudgetExpenseRepositoryInterface
- `findById(int $id): ?BudgetExpense`
- `findByUserId(int $userId): array`
- `findByBudgetId(int $budgetId): array`
- `findByDateRange(int $userId, \DateTime $start, \DateTime $end): array`
- `save(BudgetExpense $expense): BudgetExpense`
- `delete(int $id): bool`

### Controller: BudgetingController.php

**Constructor**: `__construct(BudgetingService $service)`

**Route Registration**: `registerRoutes($router): void`

**Category Endpoints**:
- `getCategories($request): array`
- `getCategory($request): array`
- `createCategory($request): array`
- `updateCategory($request): array`
- `deleteCategory($request): array`

**Budget Endpoints**:
- `getBudgets($request): array`
- `getActiveBudgets($request): array`
- `getBudget($request): array`
- `getBudgetProgress($request): array`
- `createBudget($request): array`
- `updateBudget($request): array`
- `deleteBudget($request): array`

**Expense Endpoints**:
- `getExpenses($request): array`
- `getSpendingSummary($request): array`
- `createExpense($request): array`
- `deleteExpense($request): array`

---

## B) BEHAVIOR CONTRACT

### Budget Entity

| Method | Inputs | Outputs | Side Effects | Errors |
|--------|--------|---------|--------------|--------|
| `__construct` | userId, categoryId, amount, period, startDate | Budget instance | Sets endDate = startDate + 1 period | None documented |
| `getBudgetProgress` | budgetId | array with budget, total_spent, remaining, percentage, is_over_budget, expense_count | None | Throws InvalidArgumentException if budget not found |

### BudgetingService

| Method | Inputs | Outputs | Side Effects | Errors |
|--------|--------|---------|--------------|--------|
| `createCategory` | name, type, color, userId, description? | BudgetCategory | Calls repo->save() | None documented |
| `getUserCategories` | userId | array of BudgetCategory | None | None |
| `getCategoriesByType` | userId, type | array of BudgetCategory | None | None |
| `createBudget` | userId, categoryId, amount, period, startDate, note? | Budget | Calls repo->save() | None documented |
| `getUserBudgets` | userId | array of Budget | None | None |
| `getActiveBudgets` | userId | array of Budget | None | None |
| `getBudgetProgress` | budgetId | array (budget, total_spent, remaining, percentage, is_over_budget, expense_count) | None | Throws InvalidArgumentException if budget not found |
| `createExpense` | userId, budgetId, categoryId, amount, expenseDate, description?, merchant? | BudgetExpense | Calls repo->save() | None documented |
| `getUserExpenses` | userId, start?, end? | array of BudgetExpense | None | None |
| `getSpendingSummary` | userId, start, end | array (total_spending, by_category, expense_count) | None | None |

### Controller Methods

All controller methods:
- **Input**: `$request` array with user_id, params, query, body
- **Output**: `['status' => int, 'data' => mixed]` or `['status' => int, 'error' => string]`
- **Side Effects**: Calls service methods
- **Errors**: Returns 404 status for not found

---

## C) BRANCH MAP

### BudgetingService.php

| File → Function → Branch | Description |
|--------------------------|-------------|
| `getBudgetProgress` → `if (!$budget)` | Throw exception if budget not found |
| `getBudgetProgress` → calculation | percentage = min($percentage, 100) |
| `getBudgetProgress` → calculation | is_over_budget = $totalSpent > $budget->getAmount() |
| `getUserExpenses` → `if ($start && $end)` | Use date range or all expenses |
| `getSpendingSummary` → `foreach` | Group expenses by category |

### BudgetingController.php

| File → Function → Branch | Description |
|--------------------------|-------------|
| `getCategories` → `if ($type)` | Filter by type or get all |
| `getCategory` → `foreach` + `if ($cat->getId() == $id)` | Find category by ID |
| `getCategory` → else | Return 404 |
| `createCategory` → default values | Use defaults for missing fields |
| `updateCategory` → `foreach` + `if ($cat->getId() == $id)` | Find and update |
| `updateCategory` → `if (isset($data[...]))` | Conditional updates |
| `updateCategory` → else | Return 404 |
| `getBudget` → `foreach` + `if ($budget->getId() == $id)` | Find budget by ID |
| `getBudget` → else | Return 404 |
| `getBudgetProgress` → `try/catch` | Handle InvalidArgumentException |
| `createBudget` → default values | Use defaults for missing fields |
| `updateBudget` → `foreach` + `if ($budget->getId() == $id)` | Find and update |
| `updateBudget` → `if (isset($data[...]))` | Conditional updates |
| `updateBudget` → else | Return 404 |
| `getExpenses` → `if (isset($query['start_date']))` | Parse date params |
| `getSpendingSummary` → `if (isset($query['start_date']))` | Use query params or defaults |
| `createExpense` → default values | Use defaults for missing fields |

---

## TEST REQUIREMENTS SUMMARY

### Unit Tests Needed

1. **Entity Tests** (3 files)
   - BudgetTest.php: Constructor, getters, setters, endDate calculation
   - BudgetCategoryTest.php: Constructor, getters, setters
   - BudgetExpenseTest.php: Constructor, getters, setters

2. **Service Tests** (1 file)
   - BudgetingServiceTest.php:
     - createCategory: valid data, missing optional
     - getUserCategories: with categories, empty
     - getCategoriesByType: filter by income/expense/saving
     - createBudget: valid data, calculate endDate
     - getUserBudgets: with budgets, empty
     - getActiveBudgets: mixed active/inactive
     - getBudgetProgress: success, budget not found, over budget
     - createExpense: valid data, with optional fields
     - getUserExpenses: with date range, without
     - getSpendingSummary: aggregation logic

3. **Controller Tests** (1 file)
   - BudgetingControllerTest.php: All 17 endpoints

### Integration Tests Needed

1. **BudgetingIntegrationTest.php**
   - Full workflow: create category → create budget → create expense → check progress
   - Multiple categories and budgets
   - Date range queries
   - Spending summary aggregation

---

*Analysis complete. Ready for test generation.*
