<?php

namespace App\DigitalWallet\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\DigitalWallet\Entity\WalletTransaction;

class WalletTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTransaction::class);
    }

    public function findByWalletId(string $walletId, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.walletId = :walletId')
            ->setParameter('walletId', $walletId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByWalletIdWithLimit(string $walletId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.walletId = :walletId')
            ->setParameter('walletId', $walletId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByWalletAndDateRange(string $walletId, \DateTime $start, \DateTime $end): int
    {
        return (int)$this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.walletId = :walletId')
            ->andWhere('t.createdAt BETWEEN :start AND :end')
            ->setParameter('walletId', $walletId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAmountByWalletAndDateRange(string $walletId, \DateTime $start, \DateTime $end): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(ABS(t.amount))')
            ->where('t.walletId = :walletId')
            ->andWhere('t.createdAt BETWEEN :start AND :end')
            ->setParameter('walletId', $walletId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
        return (int)($result ?: 0);
    }

    public function findByReferenceId(string $referenceId): ?WalletTransaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.referenceId = :referenceId')
            ->setParameter('referenceId', $referenceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingTransactions(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTransactionStats(string $walletId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.transactionType, SUM(t.amount) as totalAmount, COUNT(t.id) as count')
            ->andWhere('t.walletId = :walletId')
            ->andWhere('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.status = :status')
            ->setParameter('walletId', $walletId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'completed')
            ->groupBy('t.transactionType')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByWallet(string $walletId, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.walletId = :walletId')
            ->setParameter('walletId', $walletId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
