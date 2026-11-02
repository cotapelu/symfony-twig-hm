<?php

declare(strict_types=1);

namespace App\Health\Repository;

use App\Health\Entity\HealthProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HealthProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthProfile::class);
    }

    public function findByUserId(int $userId): ?HealthProfile
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserIds(array $userIds): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.userId IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->getQuery()
            ->getResult();
    }
}