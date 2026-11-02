<?php

declare(strict_types=1);

namespace App\Queue\Domain\Repository;

use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;

interface QueueRepositoryInterface
{
    public function findById(string $id): ?Queue;

    public function findByWardCode(string $wardCode): array;

    public function findActiveQueues(): array;

    public function findOpenQueues(): array;

    public function save(Queue $queue): void;

    public function remove(Queue $queue): void;

    public function incrementAndGetNextSequenceNumber(string $queueId): int;
}

interface QueueTicketRepositoryInterface
{
    public function findById(string $id): ?QueueTicket;

    public function findByTicketNumber(string $ticketNumber): ?QueueTicket;

    public function findByQueue(string $queueId, ?string $status = null, ?int $limit = null, ?int $offset = null): array;

    public function findByCitizen(string $citizenId): array;

    public function findByCitizenPhone(string $phone): array;

    public function findWaitingTickets(string $queueId): array;

    public function findCalledTickets(string $queueId): array;

    public function findNextWaitingTicket(string $queueId): ?QueueTicket;

    public function save(QueueTicket $ticket): void;

    public function remove(QueueTicket $ticket): void;

    public function countWaitingTickets(string $queueId): int;

    public function countCompletedTickets(string $queueId, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): int;
}
