<?php

declare(strict_types=1);

namespace App\Health\Repository;

use App\Health\Entity\HealthRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HealthRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthRecord::class);
    }

    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByUserIdAndType(int $userId, string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.userId = :userId')
            ->andWhere('h.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findLatestByUserId(int $userId, string $type): ?HealthRecord
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.userId = :userId')
            ->andWhere('h.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByUserId(int $userId): int
    {
        $result = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}