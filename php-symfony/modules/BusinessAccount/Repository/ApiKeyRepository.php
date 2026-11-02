<?php

declare(strict_types=1);

namespace App\BusinessAccount\Repository;

use App\BusinessAccount\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findByBusiness(int $businessId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByBusiness(int $businessId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('a.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findByKey(string $key): ?ApiKey
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.key = :key')
            ->setParameter('key', $key)
            ->andWhere('a.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function revokeOldKeys(int $businessId, int $keepCount = 2): int
    {
        $ids = $this->findRecentActiveKeyIds($businessId, $keepCount);
        return $this->bulkRevokeExceptIds($businessId, $ids);
    }

    private function findRecentActiveKeyIds(int $businessId, int $keepCount): array
    {
        $keys = $this->createQueryBuilder('a')
            ->andWhere('a.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('a.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($keepCount)
            ->getQuery()
            ->getResult();
        return array_map(fn($key) => $key->getId(), $keys);
    }

    private function bulkRevokeExceptIds(int $businessId, array $ids): int
    {
        return $this->createQueryBuilder('a')
            ->update()
            ->set('a.isActive', 'false')
            ->andWhere('a.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('a.id NOT IN (:ids)')
            ->setParameter('ids', $ids ?: [0])
            ->andWhere('a.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->execute();
    }
}
