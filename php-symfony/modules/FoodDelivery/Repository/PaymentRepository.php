<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\Payment;

class PaymentRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByOrder(int $orderId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.order = :orderId')
            ->setParameter('orderId', $orderId)
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

    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }
}
