<?php

declare(strict_types=1);

namespace App\Queue\Infrastructure\Repository;

use Doctrine\ORM\EntityRepository;
use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Queue\Domain\Repository\QueueRepositoryInterface;
use App\Queue\Domain\Repository\QueueTicketRepositoryInterface;
use Doctrine\DBAL\LockMode;

class QueueRepository extends EntityRepository implements QueueRepositoryInterface
{
    public function findById(string $id): ?Queue
    {
        return $this->find($id);
    }

    public function findByWardCode(string $wardCode): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveQueues(): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->setParameter('status', Queue::STATUS_ACTIVE)
            ->orderBy('q.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOpenQueues(): array
    {
        $now = new \DateTime();
        $dayOfWeek = (int)$now->format('N');
        $currentTime = (int)$now->format('Hi');

        $qb = $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->setParameter('status', Queue::STATUS_ACTIVE);

        $qb->andWhere(
            $qb->expr()->orX(
                'q.operatingHours IS NULL',
                $qb->expr()->like('q.operatingHours', $qb->expr()->literal('%"' . $dayOfWeek . '"%'))
            )
        );

        $qb->orderBy('q.name', 'ASC');

        return array_filter($qb->getQuery()->getResult(), fn($q) => $q->isOpen());
    }

    public function save(Queue $queue): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($queue)) {
            $em->persist($queue);
        }
        $em->flush();
    }

    public function remove(Queue $queue): void
    {
        $em = $this->getEntityManager();
        $em->remove($queue);
        $em->flush();
    }

    public function incrementAndGetNextSequenceNumber(string $queueId): int
    {
        $em = $this->getEntityManager();
        $queue = $this->find($queueId);
        if (!$queue) {
            throw new \InvalidArgumentException('Queue not found');
        }

        // Acquire pessimistic write lock
        $em->lock($queue, LockMode::PESSIMISTIC_WRITE);

        $newSeq = $queue->getCurrentSequence() + 1;
        $queue->setCurrentSequence($newSeq);
        // Flush immediately to persist the increment and release lock
        $em->flush();

        return $newSeq;
    }
}

class QueueTicketRepository extends EntityRepository implements QueueTicketRepositoryInterface
{
    public function findById(string $id): ?QueueTicket
    {
        return $this->find($id);
    }

    public function findByTicketNumber(string $ticketNumber): ?QueueTicket
    {
        return $this->createQueryBuilder('t')
            ->where('t.ticketNumber = :ticketNumber')
            ->setParameter('ticketNumber', $ticketNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByQueue(string $queueId, ?string $status = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->setParameter('queueId', $queueId);

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('t.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCitizen(string $citizenId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.citizen', 'c')
            ->where('c.id = :citizenId')
            ->setParameter('citizenId', $citizenId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCitizenPhone(string $phone): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.citizenPhone = :phone')
            ->setParameter('phone', $phone)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWaitingTickets(string $queueId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->andWhere('t.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', QueueTicket::STATUS_WAITING)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCalledTickets(string $queueId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->andWhere('t.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', QueueTicket::STATUS_CALLED)
            ->orderBy('t.calledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNextWaitingTicket(string $queueId): ?QueueTicket
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->andWhere('t.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', QueueTicket::STATUS_WAITING)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createdAt', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function save(QueueTicket $ticket): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($ticket)) {
            $em->persist($ticket);
        }
        $em->flush();
    }

    public function remove(QueueTicket $ticket): void
    {
        $em = $this->getEntityManager();
        $em->remove($ticket);
        $em->flush();
    }

    public function countWaitingTickets(string $queueId): int
    {
        return (int)$this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->andWhere('t.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', QueueTicket::STATUS_WAITING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedTickets(
        string $queueId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): int {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.queue', 'q')
            ->where('q.id = :queueId')
            ->andWhere('t.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', QueueTicket::STATUS_COMPLETED);

        if ($startDate !== null) {
            $qb->andWhere('t.completedAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('t.completedAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
