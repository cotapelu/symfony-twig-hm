<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\Restaurant;

class RestaurantRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    private const STATUS_ACTIVE = 'active';
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    public function findActiveRestaurants(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->orderBy('r.rating', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countActiveRestaurants(): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCuisine(string $cuisine, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.cuisine = :cuisine')
            ->andWhere('r.status = :status')
            ->setParameter('cuisine', $cuisine)
            ->setParameter('status', self::STATUS_ACTIVE)
            ->orderBy('r.rating', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByCuisine(string $cuisine): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.cuisine = :cuisine')
            ->andWhere('r.status = :status')
            ->setParameter('cuisine', $cuisine)
            ->setParameter('status', self::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findNearbyRestaurants(float $latitude, float $longitude, float $radiusKm = 5.0, int $limit = 50, int $offset = 0): array
    {
        $haversineFormula = '(6371 * acos(cos(radians(:latitude)) * cos(radians(r.latitude)) * cos(radians(r.longitude) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(r.latitude))))';

        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->andWhere($haversineFormula . ' < :radius')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('radius', $radiusKm)
            ->orderBy($haversineFormula, 'ASC')
            ->addOrderBy('r.rating', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countNearbyRestaurants(float $latitude, float $longitude, float $radiusKm = 5.0): int
    {
        $haversineFormula = '(6371 * acos(cos(radians(:latitude)) * cos(radians(r.latitude)) * cos(radians(r.longitude) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(r.latitude))))';

        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->andWhere($haversineFormula . ' < :radius')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('radius', $radiusKm)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function searchRestaurants(string $keyword, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.name LIKE :keyword OR r.cuisine LIKE :keyword')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('r.rating', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countSearchRestaurants(string $keyword): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->andWhere('r.name LIKE :keyword OR r.cuisine LIKE :keyword')
            ->setParameter('status', self::STATUS_ACTIVE)
            ->setParameter('keyword', '%' . $keyword . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByOwner(string $ownerId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByOwner(string $ownerId): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Restaurant $restaurant): void
    {
        $this->getEntityManager()->persist($restaurant);
        $this->getEntityManager()->flush();
    }
}
