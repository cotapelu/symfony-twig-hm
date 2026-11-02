<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\BudgetExpense;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;

/**
 * PDO-based repository for BudgetExpense entity.
 */
class BudgetExpenseRepository implements BudgetExpenseRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?BudgetExpense
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_expenses WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_expenses WHERE user_id = :userId AND deleted_at IS NULL ORDER BY expense_date DESC"
        );
        $stmt->execute([':userId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findByBudgetId(int $budgetId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_expenses WHERE budget_id = :budgetId AND deleted_at IS NULL ORDER BY expense_date DESC"
        );
        $stmt->execute([':budgetId' => $budgetId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findByDateRange(int $userId, DateTimeInterface $start, DateTimeInterface $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_expenses 
             WHERE user_id = :userId 
               AND expense_date >= :start 
               AND expense_date <= :end 
               AND deleted_at IS NULL 
             ORDER BY expense_date DESC"
        );
        $stmt->execute([
            ':userId' => $userId,
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function save(BudgetExpense $expense): BudgetExpense
    {
        if ($expense->getId() === null) {
            return $this->insert($expense);
        }

        return $this->update($expense);
    }

    private function insert(BudgetExpense $expense): BudgetExpense
    {
        $sql = "INSERT INTO budget_expenses 
                (user_id, budget_id, category_id, amount, description, merchant, transaction_id, expense_date, created_at, updated_at) 
                VALUES (:user_id, :budget_id, :category_id, :amount, :description, :merchant, :transaction_id, :expense_date, :created_at, :updated_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $expense->getUserId(),
            ':budget_id' => $expense->getBudgetId(),
            ':category_id' => $expense->getCategoryId(),
            ':amount' => $expense->getAmount(),
            ':description' => $expense->getDescription(),
            ':merchant' => $expense->getMerchant(),
            ':transaction_id' => $expense->getTransactionId(),
            ':expense_date' => $expense->getExpenseDate()->format('Y-m-d H:i:s'),
            ':created_at' => $expense->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated_at' => $expense->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        $expenseId = (int)$this->pdo->lastInsertId();
        
        $reflection = new \ReflectionClass($expense);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($expense, $expenseId);

        return $expense;
    }

    private function update(BudgetExpense $expense): BudgetExpense
    {
        $sql = "UPDATE budget_expenses SET 
                amount = :amount, 
                description = :description, 
                merchant = :merchant, 
                transaction_id = :transaction_id, 
                expense_date = :expense_date, 
                updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':amount' => $expense->getAmount(),
            ':description' => $expense->getDescription(),
            ':merchant' => $expense->getMerchant(),
            ':transaction_id' => $expense->getTransactionId(),
            ':expense_date' => $expense->getExpenseDate()->format('Y-m-d H:i:s'),
            ':updated_at' => $expense->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id' => $expense->getId(),
        ]);

        return $expense;
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE budget_expenses SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function mapRowToEntity(array $row): BudgetExpense
    {
        $expenseDate = new DateTimeImmutable($row['expense_date']);

        $expense = new BudgetExpense(
            (int)$row['user_id'],
            (int)$row['budget_id'],
            (int)$row['category_id'],
            (float)$row['amount'],
            $expenseDate
        );

        $this->setId($expense, (int)$row['id']);
        $this->setDescription($expense, $row);
        $this->setMerchant($expense, $row);
        $this->setTransactionId($expense, $row);
        $this->setCreatedAt($expense, $row);
        $this->setUpdatedAt($expense, $row);
        $this->setDeletedAt($expense, $row);

        return $expense;
    }

    private function setId(BudgetExpense $expense, int $id): void
    {
        $reflection = new \ReflectionClass($expense);
        $prop = $reflection->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($expense, $id);
    }

    private function setDescription(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('description', $row) && $row['description'] !== null) {
            $this->setProperty($expense, 'description', $row['description']);
        }
    }

    private function setMerchant(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('merchant', $row) && $row['merchant'] !== null) {
            $this->setProperty($expense, 'merchant', $row['merchant']);
        }
    }

    private function setTransactionId(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('transaction_id', $row) && $row['transaction_id'] !== null) {
            $this->setProperty($expense, 'transactionId', (int)$row['transaction_id']);
        }
    }

    private function setCreatedAt(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('created_at', $row)) {
            $this->setProperty($expense, 'createdAt', new DateTimeImmutable($row['created_at']));
        }
    }

    private function setUpdatedAt(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('updated_at', $row)) {
            $this->setProperty($expense, 'updatedAt', new DateTimeImmutable($row['updated_at']));
        }
    }

    private function setDeletedAt(BudgetExpense $expense, array $row): void
    {
        if (array_key_exists('deleted_at', $row) && $row['deleted_at'] !== null) {
            $this->setProperty($expense, 'deletedAt', new DateTimeImmutable($row['deleted_at']));
        }
    }

    private function setProperty(BudgetExpense $expense, string $property, $value): void
    {
        $reflection = new \ReflectionClass($expense);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($expense, $value);
    }
}
