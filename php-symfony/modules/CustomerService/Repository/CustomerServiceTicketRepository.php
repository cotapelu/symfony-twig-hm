<?php

declare(strict_types=1);

namespace App\CustomerService\Repository;

use App\CustomerService\Entity\CustomerServiceTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerServiceTicket>
 */
class CustomerServiceTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerServiceTicket::class);
    }

    public function save(CustomerServiceTicket $ticket): void
    {
        $this->getEntityManager()->persist($ticket);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?CustomerServiceTicket
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByUser(string $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAgent(string $agentId, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.assignedTo = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC'], $limit);
    }

    public function findOpenTickets(int $limit = 100): array
    {
        return $this->findByStatus('open', $limit);
    }

    public function updateStatus(string $ticketId, string $status): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.status', ':status')
            ->set('t.updatedAt', ':now')
            ->where('t.id = :id')
            ->setParameter('status', $status)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $ticketId)
            ->getQuery()
            ->execute();
    }

    public function assignToAgent(string $ticketId, string $agentId): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.assignedTo', ':agentId')
            ->set('t.updatedAt', ':now')
            ->where('t.id = :id')
            ->setParameter('agentId', $agentId)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $ticketId)
            ->getQuery()
            ->execute();
    }

    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenByAgent(string $agentId): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :agentId')
            ->andWhere('t.status != :closed')
            ->setParameter('agentId', $agentId)
            ->setParameter('closed', 'closed')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
