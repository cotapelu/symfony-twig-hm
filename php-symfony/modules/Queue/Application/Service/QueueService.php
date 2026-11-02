<?php

declare(strict_types=1);

namespace App\Queue\Application\Service;

use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Queue\Domain\Repository\QueueRepositoryInterface;
use App\Queue\Domain\Repository\QueueTicketRepositoryInterface;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Entity\Officer;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;

class QueueService
{
    public function __construct(
        private QueueRepositoryInterface $queueRepository,
        private QueueTicketRepositoryInterface $ticketRepository
    ) {}

    public function createQueue(
        string $name,
        string $wardCode,
        array $serviceTypes,
        ?string $departmentName = null,
        ?string $description = null,
        int $counterCount = 1,
        ?array $operatingHours = null
    ): Queue {
        $queue = new Queue(
            Uuid::uuid4()->toString(),
            $name,
            $wardCode,
            $serviceTypes,
            $departmentName
        );

        $queue->setDescription($description);
        $queue->setCounterCount($counterCount);
        $queue->setOperatingHours($operatingHours);

        $this->queueRepository->save($queue);

        return $queue;
    }

    public function updateQueue(
        string $queueId,
        ?string $name = null,
        ?array $serviceTypes = null,
        ?string $description = null,
        ?int $counterCount = null,
        ?array $operatingHours = null
    ): ?Queue {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            return null;
        }

        if ($name !== null) {
            $queue->setName($name);
        }

        if ($serviceTypes !== null) {
            $queue->setServiceTypes($serviceTypes);
        }

        if ($description !== null) {
            $queue->setDescription($description);
        }

        if ($counterCount !== null) {
            $queue->setCounterCount($counterCount);
        }

        if ($operatingHours !== null) {
            $queue->setOperatingHours($operatingHours);
        }

        $this->queueRepository->save($queue);

        return $queue;
    }

    public function activateQueue(string $queueId): ?Queue
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            return null;
        }

        $queue->activate();
        $this->queueRepository->save($queue);

        return $queue;
    }

    public function deactivateQueue(string $queueId): ?Queue
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            return null;
        }

        $queue->deactivate();
        $this->queueRepository->save($queue);

        return $queue;
    }

    public function getQueue(string $queueId): ?Queue
    {
        return $this->queueRepository->findById($queueId);
    }

    public function getQueuesByWard(string $wardCode): array
    {
        return $this->queueRepository->findByWardCode($wardCode);
    }

    public function getActiveQueues(): array
    {
        return $this->queueRepository->findActiveQueues();
    }

    public function getOpenQueues(): array
    {
        return $this->queueRepository->findOpenQueues();
    }

    public function joinQueue(
        string $queueId,
        string $serviceType,
        string $citizenName,
        ?string $citizenPhone = null,
        ?string $citizenId = null,
        int $priority = 0
    ): ?QueueTicket {
        $queue = $this->queueRepository->findById($queueId);
        if ($queue === null) {
            return null;
        }

        $this->validateQueueJoining($queue, $serviceType);
        $ticket = $this->buildTicket($queue, $serviceType, $citizenName, $citizenPhone, $priority);
        $this->ticketRepository->save($ticket);
        return $ticket;
    }

    public function callNextTicket(
        string $queueId,
        Officer $officer,
        string $counterNumber
    ): ?QueueTicket {
        $queue = $this->queueRepository->findById($queueId);
        if ($queue === null) {
            return null;
        }

        $this->ensureQueueActive($queue);
        $nextTicket = $this->ticketRepository->findNextWaitingTicket($queueId);
        if ($nextTicket === null) {
            return null;
        }

        $this->callAndSaveTicket($nextTicket, $officer, $counterNumber);
        return $nextTicket;
    }

    public function startTicket(string $ticketId, ?string $userId = null): ?QueueTicket
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket === null) {
            return null;
        }

        // Optional ownership check
        $citizenId = $ticket->getCitizen()?->getId();
        if ($userId !== null && $citizenId !== null && (string)$citizenId !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        $ticket->start();
        $this->ticketRepository->save($ticket);

        return $ticket;
    }

    public function completeTicket(string $ticketId, ?string $notes = null, ?string $userId = null): ?QueueTicket
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket === null) {
            return null;
        }

        // Optional ownership check
        $citizenId = $ticket->getCitizen()?->getId();
        if ($userId !== null && $citizenId !== null && (string)$citizenId !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        $ticket->complete($notes);
        $this->ticketRepository->save($ticket);

        $this->updateQueueStatistics($ticket->getQueue());

        return $ticket;
    }

    public function cancelTicket(string $ticketId, ?string $reason = null, ?string $userId = null): ?QueueTicket
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket === null) {
            return null;
        }

        // Optional ownership check
        $citizenId = $ticket->getCitizen()?->getId();
        if ($userId !== null && $citizenId !== null && (string)$citizenId !== $userId) {
            throw new \InvalidArgumentException('Access denied');
        }

        $ticket->cancel($reason);
        $this->ticketRepository->save($ticket);

        return $ticket;
    }

    public function skipTicket(string $ticketId, ?string $userId = null): ?QueueTicket
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket === null) {
            return null;
        }

        // Optional ownership check - only officers can skip
        $citizenId = $ticket->getCitizen()?->getId();
        if ($userId !== null && $citizenId !== null && (string)$citizenId === $userId) {
            throw new \InvalidArgumentException('Access denied: only officers can skip tickets');
        }

        $ticket->skip();
        $this->ticketRepository->save($ticket);

        return $ticket;
    }

    public function getTicket(string $ticketId): ?QueueTicket
    {
        return $this->ticketRepository->findById($ticketId);
    }

    public function getTicketByNumber(string $ticketNumber): ?QueueTicket
    {
        return $this->ticketRepository->findByTicketNumber($ticketNumber);
    }

    public function getTicketsByQueue(
        string $queueId,
        ?string $status = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->ticketRepository->findByQueue($queueId, $status, $limit, $offset);
    }

    public function getWaitingTickets(string $queueId): array
    {
        return $this->ticketRepository->findWaitingTickets($queueId);
    }

    public function getCalledTickets(string $queueId): array
    {
        return $this->ticketRepository->findCalledTickets($queueId);
    }

    public function getTicketsByCitizen(string $citizenId): array
    {
        return $this->ticketRepository->findByCitizen($citizenId);
    }

    public function getTicketsByPhone(string $phone): array
    {
        return $this->ticketRepository->findByCitizenPhone($phone);
    }

    public function getQueueStatistics(string $queueId): ?array
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            return null;
        }

        $waitingCount = $this->ticketRepository->countWaitingTickets($queueId);
        $todayCompleted = $this->ticketRepository->countCompletedTickets(
            $queueId,
            new DateTimeImmutable('today 00:00:00'),
            new DateTimeImmutable('today 23:59:59')
        );

        return [
            'queueId' => $queue->getId(),
            'queueName' => $queue->getName(),
            'isOpen' => $queue->isOpen(),
            'waitingCount' => $waitingCount,
            'inProgressCount' => count($queue->getInProgressTickets()),
            'todayCompleted' => $todayCompleted,
            'averageWaitTime' => $queue->getAverageWaitTime(),
            'averageServiceTime' => $queue->getAverageServiceTime(),
            'operatingHours' => $queue->getOperatingHours(),
        ];
    }

    private function calculateEstimatedWaitTime(Queue $queue): ?int
    {
        $waitingTickets = $this->ticketRepository->findWaitingTickets($queue->getId());
        $averageServiceTime = $queue->getAverageServiceTime();

        if ($averageServiceTime === null || empty($waitingTickets)) {
            return null;
        }

        $inProgressTickets = $queue->getInProgressTickets();
        $remainingService = count($inProgressTickets) * $averageServiceTime;

        foreach ($waitingTickets as $ticket) {
            $remainingService += $averageServiceTime;
        }

        return (int)($remainingService / 60);
    }

    private function updateQueueStatistics(Queue $queue): void
    {
        $completedTickets = array_filter(
            $queue->getTickets()->toArray(),
            fn($t) => $t->isCompleted()
        );

        if (!empty($completedTickets)) {
            $avgWaitTime = array_reduce($completedTickets, function($sum, $t) {
                return $sum + ($t->getWaitingDuration() ?? 0);
            }, 0) / count($completedTickets);

            $avgServiceTime = array_reduce($completedTickets, function($sum, $t) {
                return $sum + ($t->getServiceDuration() ?? 0);
            }, 0) / count($completedTickets);

            $queue->setAverageServiceTime((int)$avgServiceTime);
            $this->queueRepository->save($queue);
        }
    }

    private function validateQueueJoining(Queue $queue, string $serviceType): void
    {
        if (!$queue->isActive()) {
            throw new \RuntimeException('Queue is not active');
        }
        if (!$queue->isOpen()) {
            throw new \RuntimeException('Queue is not currently open');
        }
        if (!$queue->hasServiceType($serviceType)) {
            throw new \RuntimeException('Service type not available in this queue');
        }
        $maxTickets = $queue->getMaxTicketsPerDay();
        if ($maxTickets !== null) {
            $todayCompleted = $this->ticketRepository->countCompletedTickets(
                $queue->getId(),
                new DateTimeImmutable('today 00:00:00'),
                new DateTimeImmutable('today 23:59:59')
            );
            if ($todayCompleted >= $maxTickets) {
                throw new \RuntimeException('Maximum tickets per day reached');
            }
        }
    }

    private function generateTicketNumber(Queue $queue, string $serviceType): string
    {
        $nextSeq = $this->queueRepository->incrementAndGetNextSequenceNumber($queue->getId());
        $prefix = mb_strtoupper($serviceType, 'UTF-8');
        return $prefix . '-' . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    private function buildTicket(
        Queue $queue,
        string $serviceType,
        string $citizenName,
        ?string $citizenPhone,
        int $priority
    ): QueueTicket {
        $ticketNumber = $this->generateTicketNumber($queue, $serviceType);
        $ticket = new QueueTicket(
            Uuid::uuid4()->toString(),
            $queue,
            $serviceType,
            $citizenName,
            $citizenPhone,
            $priority,
            $ticketNumber
        );
        $estimatedWaitTime = $this->calculateEstimatedWaitTime($queue);
        $ticket->setEstimatedWaitTime($estimatedWaitTime);
        return $ticket;
    }

    private function ensureQueueActive(Queue $queue): void
    {
        if (!$queue->isActive()) {
            throw new \RuntimeException('Queue is not active');
        }
    }

    private function callAndSaveTicket(QueueTicket $ticket, Officer $officer, string $counterNumber): void
    {
        $ticket->call($officer, $counterNumber);
        $this->ticketRepository->save($ticket);
    }

    public function listQueues(int $page, int $limit, ?string $wardCode = null, ?string $serviceType = null, ?string $status = null): array
    {
        // Base queues
        if ($wardCode !== null) {
            $queues = $this->queueRepository->findByWardCode($wardCode);
        } else {
            $queues = $this->queueRepository->findActiveQueues();
        }

        // Apply filters
        if ($serviceType !== null) {
            $queues = array_filter($queues, fn($q) => in_array($serviceType, $q->getServiceTypes(), true));
        }
        if ($status !== null) {
            $queues = array_filter($queues, fn($q) => $q->getStatus() === $status);
        }

        $total = count($queues);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($queues, $offset, $limit);

        return [
            'data' => array_values($paged),
            'total' => $total,
        ];
    }

    public function getQueueStatus(int $queueId): ?array
    {
        $queue = $this->queueRepository->findById((string)$queueId);
        if (!$queue) {
            return null;
        }
        return $this->getQueueStatistics((string)$queueId);
    }

    public function getCitizenQueueStatus(string $userId): array
    {
        $tickets = $this->ticketRepository->findByCitizen($userId);
        foreach ($tickets as $ticket) {
            if (in_array($ticket->getStatus(), [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_CALLED, QueueTicket::STATUS_IN_PROGRESS], true)) {
                $queue = $ticket->getQueue();
                return [
                    'hasActiveTicket' => true,
                    'queueId' => $queue->getId(),
                    'queueName' => $queue->getName(),
                    'ticketNumber' => $ticket->getTicketNumber(),
                    'status' => $ticket->getStatus(),
                ];
            }
        }
        return ['hasActiveTicket' => false];
    }
}
