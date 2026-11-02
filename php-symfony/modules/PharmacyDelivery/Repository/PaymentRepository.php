<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App\PharmacyDelivery\Entity\Payment::class);
    }

    public function findByOrder(int $orderId): ?App\PharmacyDelivery\Entity\Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()->getOneOrNullResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
