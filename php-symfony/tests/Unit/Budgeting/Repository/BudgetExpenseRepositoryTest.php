<?php declare(strict_types=1);

namespace Tests\Unit\Budgeting\Repository;

use App\Budgeting\Repository\BudgetExpenseRepository;
use App\Budgeting\Entity\BudgetExpense;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class BudgetExpenseRepositoryTest extends TestCase
{
    private PDO&MockObject $pdoMock;
    private PDOStatement&MockObject $stmtMock;
    private BudgetExpenseRepository $repository;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->repository = new BudgetExpenseRepository($this->pdoMock);
    }

    private function willPrepareExecuteFetchRow(?array $row = null): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($row);
    }

    private function willPrepareExecuteFetchAll(array $rows): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($rows);
    }

    private function createBudgetExpense(array $data): BudgetExpense
    {
        $expenseDate = new DateTimeImmutable($data['expense_date'] ?? 'now');
        $expense = new BudgetExpense(
            (int)$data['user_id'],
            (int)$data['budget_id'],
            (int)$data['category_id'],
            (float)$data['amount'],
            $expenseDate
        );
        // Set optional fields via reflection
        $reflection = new \ReflectionClass($expense);
        if (isset($data['id'])) {
            $prop = $reflection->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($expense, (int)$data['id']);
        }
        if (array_key_exists('description', $data)) {
            $prop = $reflection->getProperty('description');
            $prop->setAccessible(true);
            $prop->setValue($expense, $data['description']);
        }
        if (array_key_exists('merchant', $data)) {
            $prop = $reflection->getProperty('merchant');
            $prop->setAccessible(true);
            $prop->setValue($expense, $data['merchant']);
        }
        if (array_key_exists('transaction_id', $data)) {
            $prop = $reflection->getProperty('transactionId');
            $prop->setAccessible(true);
            $prop->setValue($expense, $data['transaction_id']);
        }
        if (isset($data['created_at'])) {
            $createdAt = new DateTimeImmutable($data['created_at']);
            $prop = $reflection->getProperty('createdAt');
            $prop->setAccessible(true);
            $prop->setValue($expense, $createdAt);
        }
        if (isset($data['updated_at'])) {
            $updatedAt = new DateTimeImmutable($data['updated_at']);
            $prop = $reflection->getProperty('updatedAt');
            $prop->setAccessible(true);
            $prop->setValue($expense, $updatedAt);
        }
        if (array_key_exists('deleted_at', $data) && $data['deleted_at'] !== null) {
            $deletedAt = new DateTimeImmutable($data['deleted_at']);
            $prop = $reflection->getProperty('deletedAt');
            $prop->setAccessible(true);
            $prop->setValue($expense, $deletedAt);
        }
        return $expense;
    }

    // ==================== findById ====================

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->willPrepareExecuteFetchRow(null);

        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindById_ReturnsEntity_WhenFound(): void
    {
        $row = [
            'id' => 1,
            'user_id' => 10,
            'budget_id' => 5,
            'category_id' => 2,
            'amount' => '100.50',
            'description' => 'Groceries',
            'merchant' => 'Market',
            'transaction_id' => 'txn-123',
            'expense_date' => '2025-01-15 10:00:00',
            'created_at' => '2025-01-15 10:05:00',
            'updated_at' => '2025-01-15 10:05:00',
            'deleted_at' => null,
        ];
        $this->willPrepareExecuteFetchRow($row);

        $result = $this->repository->findById(1);
        $this->assertInstanceOf(BudgetExpense::class, $result);
        $this->assertSame(1, $result->getId());
        $this->assertSame(10, $result->getUserId());
        $this->assertSame(100.50, $result->getAmount());
        $this->assertSame('Groceries', $result->getDescription());
    }

    // ==================== findByUserId ====================

    public function testFindByUserId_ReturnsEmptyArray_WhenNone(): void
    {
        $this->willPrepareExecuteFetchAll([]);

        $result = $this->repository->findByUserId(10);
        $this->assertEmpty($result);
    }

    public function testFindByUserId_ReturnsArray_WhenFound(): void
    {
        $rows = [
            [
                'id' => 1,
                'user_id' => 10,
                'budget_id' => 5,
                'category_id' => 2,
                'amount' => '50.00',
                'description' => null,
                'merchant' => null,
                'transaction_id' => null,
                'expense_date' => '2025-01-10 12:00:00',
                'created_at' => '2025-01-10 12:01:00',
                'updated_at' => '2025-01-10 12:01:00',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'user_id' => 10,
                'budget_id' => 5,
                'category_id' => 3,
                'amount' => '75.25',
                'description' => 'Dinner',
                'merchant' => 'Restaurant',
                'transaction_id' => 'txn-456',
                'expense_date' => '2025-01-11 18:00:00',
                'created_at' => '2025-01-11 18:02:00',
                'updated_at' => '2025-01-11 18:02:00',
                'deleted_at' => null,
            ],
        ];
        $this->willPrepareExecuteFetchAll($rows);

        $result = $this->repository->findByUserId(10);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(BudgetExpense::class, $result[0]);
        $this->assertSame(1, $result[0]->getId());
        $this->assertSame(2, $result[1]->getId());
    }

    // ==================== findByBudgetId ====================

    public function testFindByBudgetId_ReturnsEmptyArray_WhenNone(): void
    {
        $this->willPrepareExecuteFetchAll([]);

        $result = $this->repository->findByBudgetId(5);
        $this->assertEmpty($result);
    }

    public function testFindByBudgetId_ReturnsArray_WhenFound(): void
    {
        $rows = [
            [
                'id' => 3,
                'user_id' => 10,
                'budget_id' => 5,
                'category_id' => 1,
                'amount' => '200.00',
                'description' => 'Office supplies',
                'merchant' => null,
                'transaction_id' => null,
                'expense_date' => '2025-01-20 09:00:00',
                'created_at' => '2025-01-20 09:05:00',
                'updated_at' => '2025-01-20 09:05:00',
                'deleted_at' => null,
            ],
        ];
        $this->willPrepareExecuteFetchAll($rows);

        $result = $this->repository->findByBudgetId(5);
        $this->assertCount(1, $result);
        $this->assertSame(3, $result[0]->getId());
        $this->assertSame(5, $result[0]->getBudgetId());
    }

    // ==================== findByDateRange ====================

    public function testFindByDateRange_ReturnsEmptyArray_WhenNone(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-31');
        $this->willPrepareExecuteFetchAll([]);

        $result = $this->repository->findByDateRange(10, $start, $end);
        $this->assertEmpty($result);
    }

    public function testFindByDateRange_ReturnsArray_WhenFound(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-31');
        $rows = [
            [
                'id' => 4,
                'user_id' => 10,
                'budget_id' => 5,
                'category_id' => 2,
                'amount' => '30.00',
                'description' => 'Coffee',
                'merchant' => 'Cafe',
                'transaction_id' => 'txn-789',
                'expense_date' => '2025-01-15 08:30:00',
                'created_at' => '2025-01-15 08:35:00',
                'updated_at' => '2025-01-15 08:35:00',
                'deleted_at' => null,
            ],
        ];
        $this->willPrepareExecuteFetchAll($rows);

        $result = $this->repository->findByDateRange(10, $start, $end);
        $this->assertCount(1, $result);
        $this->assertSame(4, $result[0]->getId());
    }

    // ==================== save (insert & update) ====================

    public function testSave_Insert_SetsIdAndReturnsEntity(): void
    {
        $now = new DateTimeImmutable();
        $expense = new BudgetExpense(10, 5, 2, 100.0, $now);
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);
        $this->pdoMock->expects($this->once())->method('lastInsertId')->willReturn('123');

        $result = $this->repository->save($expense);
        $this->assertSame(123, $expense->getId());
        $this->assertSame($expense, $result);
    }

    public function testSave_Update_ReturnsSameEntity(): void
    {
        $now = new DateTimeImmutable();
        $expense = new BudgetExpense(10, 5, 2, 150.0, $now);
        // Set id via reflection
        $reflection = new \ReflectionClass($expense);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($expense, 456);

        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with($this->anything())->willReturn(true);

        $result = $this->repository->save($expense);
        $this->assertSame($expense, $result);
        $this->assertSame(456, $expense->getId());
    }

    // ==================== delete ====================

    public function testDelete_ReturnsTrue_WhenDeleted(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([':id' => 123])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('rowCount')->willReturn(1);

        $result = $this->repository->delete(123);
        $this->assertTrue($result);
    }

    public function testDelete_ReturnsFalse_WhenNone(): void
    {
        $this->pdoMock->expects($this->once())->method('prepare')->with($this->anything())->willReturn($this->stmtMock);
        $this->stmtMock->expects($this->once())->method('execute')->with([':id' => 456])->willReturn(true);
        $this->stmtMock->expects($this->once())->method('rowCount')->willReturn(0);

        $result = $this->repository->delete(456);
        $this->assertFalse($result);
    }
}
