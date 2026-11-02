<?php

namespace App\CustomerService\Repository;

use App\CustomerService\Entity\Ticket;
use App\CustomerService\Repository\TicketRepositoryInterface;

class TicketRepository implements TicketRepositoryInterface
{
    private \PDO $pdo;
    private string $table = 'customer_service_tickets';

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Ticket
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateTicket($row);
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE status = ? AND deleted_at IS NULL");
        $stmt->execute([$status]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    public function findByPriority(string $priority): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE priority = ? AND deleted_at IS NULL");
        $stmt->execute([$priority]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    public function findByCategory(string $category): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE category = ? AND deleted_at IS NULL");
        $stmt->execute([$category]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    public function save(Ticket $ticket): Ticket
    {
        if ($ticket->getId() === null) {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_id, subject, description, category, priority, status, assigned_to, order_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $ticket->getUserId(),
                $ticket->getSubject(),
                $ticket->getDescription(),
                $ticket->getCategory(),
                $ticket->getPriority(),
                $ticket->getStatus(),
                $ticket->getAssignedTo(),
                $ticket->getOrderId()
            ]);
            $ticket->setId((int)$this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table}
                SET description = ?, priority = ?, status = ?, assigned_to = ?, order_id = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([
                $ticket->getDescription(),
                $ticket->getPriority(),
                $ticket->getStatus(),
                $ticket->getAssignedTo(),
                $ticket->getOrderId(),
                $ticket->getId()
            ]);
        }

        return $ticket;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = datetime('now') WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findOpenTickets(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} WHERE status IN ('open', 'in_progress', 'waiting') AND deleted_at IS NULL");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    public function findAssignedTickets(int $agentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE assigned_to = ? AND deleted_at IS NULL");
        $stmt->execute([$agentId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrateTicket'], $rows);
    }

    private function hydrateTicket(array $row): Ticket
    {
        $ticket = new Ticket(
            (int)$row['user_id'],
            $row['subject'],
            $row['category']
        );
        $ticket->setId((int)$row['id']);
        $ticket->setDescription($row['description']);
        $ticket->setPriority($row['priority']);
        $ticket->setStatus($row['status']);
        $ticket->setAssignedTo($row['assigned_to'] ? (int)$row['assigned_to'] : null);
        $ticket->setOrderId($row['order_id'] ? (int)$row['order_id'] : null);

        return $ticket;
    }
}
