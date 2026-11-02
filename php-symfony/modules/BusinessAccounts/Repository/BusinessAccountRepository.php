<?php

namespace App\BusinessAccounts\Repository;

use App\BusinessAccounts\Entity\BusinessAccount;
use App\BusinessAccounts\Repository\BusinessAccountRepositoryInterface;

class BusinessAccountRepository implements BusinessAccountRepositoryInterface
{
    private \PDO $pdo;
    private string $table = 'business_accounts';

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?BusinessAccount
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateAccount($row);
    }

    public function findByUserId(string $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, $limit, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateAccount'], $rows);
    }

    public function find(?int $id): ?BusinessAccount
    {
        if ($id === null) {
            return null;
        }
        return $this->findById($id);
    }

    public function findOneByUser(string $userId): ?BusinessAccount
    {
        $result = $this->findByUserId($userId, 1, 0);
        return $result[0] ?? null;
    }

    public function countByUser(string $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function save(BusinessAccount $account): BusinessAccount
    {
        if ($account->getId() === null) {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_id, business_name, business_type, tax_id, address, phone, email, whatsapp_business_id, is_verified, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $account->getUserId(),
                $account->getBusinessName(),
                $account->getBusinessType(),
                $account->getTaxId(),
                $account->getAddress(),
                $account->getPhone(),
                $account->getEmail(),
                $account->getWhatsappBusinessId(),
                $account->isVerified() ? 1 : 0
            ]);
            $account->setId((int)$this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table}
                SET business_name = ?, business_type = ?, tax_id = ?, address = ?, phone = ?, email = ?, whatsapp_business_id = ?, is_verified = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([
                $account->getBusinessName(),
                $account->getBusinessType(),
                $account->getTaxId(),
                $account->getAddress(),
                $account->getPhone(),
                $account->getEmail(),
                $account->getWhatsappBusinessId(),
                $account->isVerified() ? 1 : 0,
                $account->getId()
            ]);
        }

        return $account;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = datetime('now') WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findVerified(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} WHERE is_verified = 1 AND deleted_at IS NULL");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateAccount'], $rows);
    }



    public function searchByUserIdAndName(int $userId, string $name, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND business_name LIKE ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$userId, "%$name%", $limit, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateAccount'], $rows);
    }

    public function searchByName(string $name): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE business_name LIKE ? AND deleted_at IS NULL");
        $stmt->execute(["%$name%"]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateAccount'], $rows);
    }

    public function findByType(string $type): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE business_type = ? AND deleted_at IS NULL");
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateAccount'], $rows);
    }

    public function findByTaxId(string $taxId): ?BusinessAccount
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE tax_id = ? AND deleted_at IS NULL");
        $stmt->execute([$taxId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->hydrateAccount($row) : null;
    }

    private function hydrateAccount(array $row): BusinessAccount
    {
        $account = new BusinessAccount(
            (int)$row['user_id'],
            $row['business_name'],
            $row['business_type']
        );
        $account->setId((int)$row['id']);
        $account->setTaxId($row['tax_id']);
        $account->setAddress($row['address']);
        $account->setPhone($row['phone']);
        $account->setEmail($row['email']);
        $account->setWhatsappBusinessId($row['whatsapp_business_id']);
        $account->setVerified((bool)$row['is_verified']);

        return $account;
    }
}
