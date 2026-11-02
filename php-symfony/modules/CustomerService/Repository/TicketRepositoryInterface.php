<?php

namespace App\CustomerService\Repository;

use App\CustomerService\Entity\Ticket;

interface TicketRepositoryInterface
{
    public function findById(int $id): ?Ticket;
    public function findByUserId(int $userId): array;
    public function findByStatus(string $status): array;
    public function findByPriority(string $priority): array;
    public function findByCategory(string $category): array;
    public function save(Ticket $ticket): Ticket;
    public function delete(int $id): bool;
    public function findOpenTickets(): array;
    public function findAssignedTickets(int $agentId): array;
}
