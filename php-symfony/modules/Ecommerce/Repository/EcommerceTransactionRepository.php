<?php

declare(strict_types=1);

namespace App\Ecommerce\Repository;

use App\Ecommerce\Entity\EcommerceTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EcommerceTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcommerceTransaction::class);
    }

    public function save(EcommerceTransaction $transaction): void
    {
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }

    public function delete(EcommerceTransaction $transaction): void
    {
        $this->getEntityManager()->remove($transaction);
        $this->getEntityManager()->flush();
    }

    public function findByOrderId(int $orderId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPaymentMethod(string $paymentMethod): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.paymentMethod = :paymentMethod')
            ->setParameter('paymentMethod', $paymentMethod)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.transactionDate >= :startDate')
            ->andWhere('t.transactionDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findSuccessfulTransactions(): array
    {
        return $this->findByStatus('success');
    }

    public function findFailedTransactions(): array
    {
        return $this->findByStatus('failed');
    }
}