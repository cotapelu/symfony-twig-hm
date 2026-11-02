<?php

namespace App\Subscription\Repository;

use App\Subscription\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    public function findActivePlans(): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('sp.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(string $planId): ?SubscriptionPlan
    {
        return $this->find($planId);
    }

    public function findAllActive(): array
    {
        return $this->findActivePlans();
    }
    
    public function save(SubscriptionPlan $plan): void
    {
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();
    }
}
