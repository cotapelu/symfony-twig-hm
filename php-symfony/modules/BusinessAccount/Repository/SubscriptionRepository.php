<?php

declare(strict_types=1);

namespace App\BusinessAccount\Repository;

use App\BusinessAccount\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findByBusiness(int $businessId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByBusiness(int $businessId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiringSubscriptions(int $daysBeforeExpiry = 7): array
    {
        $futureDate = new \DateTime('+' . $daysBeforeExpiry . ' days');
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('s.expiresAt <= :futureDate')
            ->setParameter('futureDate', $futureDate)
            ->andWhere('s.expiresAt > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findExpiredSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('s.expiresAt <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findByPlan(string $plan): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.plan = :plan')
            ->setParameter('plan', $plan)
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
}
