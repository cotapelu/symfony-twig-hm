<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\Budget;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;

/**
 * PDO-based repository for Budget entity.
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Budget
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budgets WHERE id = :id AND deleted_at IS NULL"
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
            "SELECT * FROM budgets WHERE user_id = :userId AND deleted_at IS NULL ORDER BY start_date DESC"
        );
        $stmt->execute([':userId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findActiveByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budgets WHERE user_id = :userId AND is_active = 1 AND deleted_at IS NULL ORDER BY start_date DESC"
        );
        $stmt->execute([':userId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findByCategoryId(int $categoryId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budgets WHERE category_id = :categoryId AND deleted_at IS NULL ORDER BY start_date DESC"
        );
        $stmt->execute([':categoryId' => $categoryId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function save(Budget $budget): Budget
    {
        if ($budget->getId() === null) {
            return $this->insert($budget);
        }

        return $this->update($budget);
    }

    private function insert(Budget $budget): Budget
    {
        $sql = "INSERT INTO budgets 
                (user_id, category_id, amount, period, start_date, end_date, note, is_active, created_at, updated_at) 
                VALUES (:user_id, :category_id, :amount, :period, :start_date, :end_date, :note, :is_active, :created_at, :updated_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $budget->getUserId(),
            ':category_id' => $budget->getCategoryId(),
            ':amount' => $budget->getAmount(),
            ':period' => $budget->getPeriod(),
            ':start_date' => $budget->getStartDate()->format('Y-m-d H:i:s'),
            ':end_date' => $budget->getEndDate()->format('Y-m-d H:i:s'),
            ':note' => $budget->getNote(),
            ':is_active' => $budget->isActive() ? 1 : 0,
            ':created_at' => $budget->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated_at' => $budget->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        $budgetId = (int)$this->pdo->lastInsertId();
        
        $reflection = new \ReflectionClass($budget);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($budget, $budgetId);

        return $budget;
    }

    private function update(Budget $budget): Budget
    {
        $sql = "UPDATE budgets SET 
                amount = :amount, 
                period = :period, 
                start_date = :start_date, 
                end_date = :end_date, 
                note = :note, 
                is_active = :is_active, 
                updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':amount' => $budget->getAmount(),
            ':period' => $budget->getPeriod(),
            ':start_date' => $budget->getStartDate()->format('Y-m-d H:i:s'),
            ':end_date' => $budget->getEndDate()->format('Y-m-d H:i:s'),
            ':note' => $budget->getNote(),
            ':is_active' => $budget->isActive() ? 1 : 0,
            ':updated_at' => $budget->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id' => $budget->getId(),
        ]);

        return $budget;
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE budgets SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function mapRowToEntity(array $row): Budget
    {
        $startDate = new DateTimeImmutable($row['start_date']);
        $endDate = new DateTimeImmutable($row['end_date']);

        $budget = new Budget(
            (int)$row['user_id'],
            (int)$row['category_id'],
            (float)$row['amount'],
            $row['period'],
            $startDate
        );

        // Override calculated end date with stored value
        $reflection = new \ReflectionClass($budget);
        
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($budget, (int)$row['id']);

        $endDateProp = $reflection->getProperty('endDate');
        $endDateProp->setAccessible(true);
        $endDateProp->setValue($budget, $endDate);

        if (array_key_exists('note', $row) && $row['note'] !== null) {
            $noteProp = $reflection->getProperty('note');
            $noteProp->setAccessible(true);
            $noteProp->setValue($budget, $row['note']);
        }

        $activeProp = $reflection->getProperty('isActive');
        $activeProp->setAccessible(true);
        $activeProp->setValue($budget, (bool)$row['is_active']);

        if (array_key_exists('created_at', $row)) {
            $createdProp = $reflection->getProperty('createdAt');
            $createdProp->setAccessible(true);
            $createdProp->setValue($budget, new DateTimeImmutable($row['created_at']));
        }

        if (array_key_exists('updated_at', $row)) {
            $updatedProp = $reflection->getProperty('updatedAt');
            $updatedProp->setAccessible(true);
            $updatedProp->setValue($budget, new DateTimeImmutable($row['updated_at']));
        }

        if (array_key_exists('deleted_at', $row) && $row['deleted_at'] !== null) {
            $deletedProp = $reflection->getProperty('deletedAt');
            $deletedProp->setAccessible(true);
            $deletedProp->setValue($budget, new DateTimeImmutable($row['deleted_at']));
        }

        return $budget;
    }
}
