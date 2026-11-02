<?php

declare(strict_types=1);

namespace App\RideHailing\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\RideHailing\Entity\Payment;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByRide(int $rideId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ride = :rideId')
            ->setParameter('rideId', $rideId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.transactionId = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Payment::STATUS_PENDING)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
