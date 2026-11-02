<?php

declare(strict_types=1);

namespace App\Fitness\Repository;

use App\Fitness\Entity\Goal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Goal::class);
    }

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.userId = :userId')
            ->andWhere('g.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'in_progress')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $userId, string $goalType): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.userId = :userId')
            ->andWhere('g.goalType = :goalType')
            ->setParameter('userId', $userId)
            ->setParameter('goalType', $goalType)
            ->getQuery()
            ->getResult();
    }

    public function save(Goal $goal): void
    {
        $this->getEntityManager()->persist($goal);
        $this->getEntityManager()->flush();
    }

    public function delete(Goal $goal): void
    {
        $this->getEntityManager()->remove($goal);
        $this->getEntityManager()->flush();
    }
}
