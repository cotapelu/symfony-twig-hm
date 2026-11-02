<?php

declare(strict_types=1);

namespace App\Budgeting\Repository;

use App\Budgeting\Entity\BudgetCategory;
use DateTimeImmutable;
use PDO;

/**
 * PDO-based repository for BudgetCategory entity.
 */
class BudgetCategoryRepository implements BudgetCategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?BudgetCategory
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_categories WHERE id = :id AND deleted_at IS NULL"
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
            "SELECT * FROM budget_categories WHERE user_id = :userId AND deleted_at IS NULL ORDER BY name ASC"
        );
        $stmt->execute([':userId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findByType(int $userId, string $type): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_categories WHERE user_id = :userId AND type = :type AND deleted_at IS NULL ORDER BY name ASC"
        );
        $stmt->execute([':userId' => $userId, ':type' => $type]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function findDefaults(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_categories WHERE is_default = 1 AND deleted_at IS NULL"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function save(BudgetCategory $category): BudgetCategory
    {
        if ($category->getId() === null) {
            return $this->insert($category);
        }

        return $this->update($category);
    }

    private function insert(BudgetCategory $category): BudgetCategory
    {
        $sql = "INSERT INTO budget_categories 
                (name, type, description, color, user_id, parent_id, is_default, created_at, updated_at) 
                VALUES (:name, :type, :description, :color, :user_id, :parent_id, :is_default, :created_at, :updated_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $category->getName(),
            ':type' => $category->getType(),
            ':description' => $category->getDescription(),
            ':color' => $category->getColor(),
            ':user_id' => $category->getUserId(),
            ':parent_id' => $category->getParentId(),
            ':is_default' => $category->isDefault() ? 1 : 0,
            ':created_at' => $category->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated_at' => $category->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        $categoryId = (int)$this->pdo->lastInsertId();
        
        // Use reflection to set private id (or provide setId method)
        $reflection = new \ReflectionClass($category);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($category, $categoryId);

        return $category;
    }

    private function update(BudgetCategory $category): BudgetCategory
    {
        $sql = "UPDATE budget_categories SET 
                name = :name, 
                type = :type, 
                description = :description, 
                color = :color, 
                parent_id = :parent_id, 
                is_default = :is_default, 
                updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $category->getName(),
            ':type' => $category->getType(),
            ':description' => $category->getDescription(),
            ':color' => $category->getColor(),
            ':parent_id' => $category->getParentId(),
            ':is_default' => $category->isDefault() ? 1 : 0,
            ':updated_at' => $category->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id' => $category->getId(),
        ]);

        return $category;
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE budget_categories SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function mapRowToEntity(array $row): BudgetCategory
    {
        $category = new BudgetCategory(
            $row['name'],
            $row['type'],
            $row['color'],
            (int)$row['user_id'],
            $row['description'] ?? null
        );

        // Set properties via reflection (id, parent_id, is_default, timestamps)
        $reflection = new \ReflectionClass($category);
        
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($category, (int)$row['id']);

        if (array_key_exists('parent_id', $row) && $row['parent_id'] !== null) {
            $parentProp = $reflection->getProperty('parentId');
            $parentProp->setAccessible(true);
            $parentProp->setValue($category, (int)$row['parent_id']);
        }

        if (array_key_exists('is_default', $row)) {
            $defaultProp = $reflection->getProperty('isDefault');
            $defaultProp->setAccessible(true);
            $defaultProp->setValue($category, (bool)$row['is_default']);
        }

        if (array_key_exists('created_at', $row)) {
            $createdProp = $reflection->getProperty('createdAt');
            $createdProp->setAccessible(true);
            $createdProp->setValue($category, new DateTimeImmutable($row['created_at']));
        }

        if (array_key_exists('updated_at', $row)) {
            $updatedProp = $reflection->getProperty('updatedAt');
            $updatedProp->setAccessible(true);
            $updatedProp->setValue($category, new DateTimeImmutable($row['updated_at']));
        }

        if (array_key_exists('deleted_at', $row) && $row['deleted_at'] !== null) {
            $deletedProp = $reflection->getProperty('deletedAt');
            $deletedProp->setAccessible(true);
            $deletedProp->setValue($category, new DateTimeImmutable($row['deleted_at']));
        }

        return $category;
    }
}
