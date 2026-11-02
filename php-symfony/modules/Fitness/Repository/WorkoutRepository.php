<?php

declare(strict_types=1);

namespace App\Fitness\Repository;

use App\Fitness\Entity\Workout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('w.workoutDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndDateRange(string $userId, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.userId = :userId')
            ->andWhere('w.workoutDate >= :startDate')
            ->andWhere('w.workoutDate <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('w.workoutDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $userId, string $type): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.userId = :userId')
            ->andWhere('w.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->orderBy('w.workoutDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalCaloriesByUser(string $userId): float
    {
        $result = $this->createQueryBuilder('w')
            ->select('SUM(w.caloriesBurned) as total')
            ->andWhere('w.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();

        return (float) ($result['total'] ?? 0);
    }

    public function save(Workout $workout): void
    {
        $this->getEntityManager()->persist($workout);
        $this->getEntityManager()->flush();
    }

    public function delete(Workout $workout): void
    {
        $this->getEntityManager()->remove($workout);
        $this->getEntityManager()->flush();
    }
}
