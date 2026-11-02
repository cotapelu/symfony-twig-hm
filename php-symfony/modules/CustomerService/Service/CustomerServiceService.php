<?php

namespace App\CustomerService\Service;

use App\CustomerService\Entity\Ticket;
use App\CustomerService\Repository\TicketRepositoryInterface;

class CustomerServiceService
{
    public function __construct(
        private TicketRepositoryInterface $repository
    ) {}

    public function createTicket(int $userId, string $subject, string $category)
    {
        $ticket = new Ticket($userId, $subject, $category);
        return $this->repository->save($ticket);
    }

    public function getTicket(int $id)
    {
        return $this->repository->findById($id);
    }

    public function getUserTickets(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function getAllTickets(array $filters = [])
    {
        $tickets = [];
        
        if (isset($filters['status'])) {
            $tickets = $this->repository->findByStatus($filters['status']);
        } elseif (isset($filters['priority'])) {
            $tickets = $this->repository->findByPriority($filters['priority']);
        } elseif (isset($filters['category'])) {
            $tickets = $this->repository->findByCategory($filters['category']);
        } else {
            // Return all tickets
            $allStatuses = ['open', 'in_progress', 'waiting', 'resolved', 'closed'];
            foreach ($allStatuses as $status) {
                $tickets = array_merge($tickets, $this->repository->findByStatus($status));
            }
        }
        
        return $tickets;
    }

    public function updateStatus(int $ticketId, string $status)
    {
        $ticket = $this->repository->findById($ticketId);
        if ($ticket) {
            $ticket->setStatus($status);
            return $this->repository->save($ticket);
        }
        return null;
    }

    public function assignTicket(int $ticketId, int $assignedTo)
    {
        $ticket = $this->repository->findById($ticketId);
        if ($ticket) {
            $ticket->setAssignedTo($assignedTo);
            return $this->repository->save($ticket);
        }
        return null;
    }

    public function setPriority(int $ticketId, string $priority)
    {
        $ticket = $this->repository->findById($ticketId);
        if ($ticket) {
            $ticket->setPriority($priority);
            return $this->repository->save($ticket);
        }
        return null;
    }

    public function linkToOrder(int $ticketId, int $orderId)
    {
        $ticket = $this->repository->findById($ticketId);
        if ($ticket) {
            $ticket->setOrderId($orderId);
            return $this->repository->save($ticket);
        }
        return null;
    }

    public function getOpenTickets()
    {
        return $this->repository->findOpenTickets();
    }

    public function getAssignedTickets(int $agentId)
    {
        return $this->repository->findAssignedTickets($agentId);
    }

    public function getTicketStats()
    {
        $open = $this->repository->findByStatus('open');
        $inProgress = $this->repository->findByStatus('in_progress');
        $waiting = $this->repository->findByStatus('waiting');
        $resolved = $this->repository->findByStatus('resolved');
        $closed = $this->repository->findByStatus('closed');

        return [
            'total' => count($open) + count($inProgress) + count($waiting) + count($resolved) + count($closed),
            'open' => count($open),
            'in_progress' => count($inProgress),
            'waiting' => count($waiting),
            'resolved' => count($resolved),
            'closed' => count($closed)
        ];
    }
}
