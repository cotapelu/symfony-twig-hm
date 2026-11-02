<?php declare(strict_types=1);
namespace App\GroceryDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\GroceryStore;

class GroceryStoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroceryStore::class);
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.type = :type')
            ->andWhere('gs.isOpen = :isOpen')
            ->setParameter('type', $type)
            ->setParameter('isOpen', true)
            ->orderBy('gs.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNearby(float $lat, float $lng, float $radiusKm, int $limit = 20): array
    {
        $earthRadius = 6371;
        $latRange = $radiusKm / $earthRadius;
        $lonRange = $radiusKm / ($earthRadius * cos(deg2rad($lat)));

        return $this->createQueryBuilder('gs')
            ->andWhere('gs.latitude BETWEEN :minLat AND :maxLat')
            ->andWhere('gs.longitude BETWEEN :minLon AND :maxLon')
            ->andWhere('gs.isOpen = :isOpen')
            ->setParameter('minLat', $lat - $latRange)
            ->setParameter('maxLat', $lat + $latRange)
            ->setParameter('minLon', $lng - $lonRange)
            ->setParameter('maxLon', $lng + $lonRange)
            ->setParameter('isOpen', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.name LIKE :search')
            ->andWhere('gs.isOpen = :isOpen')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->setParameter('isOpen', true)
            ->orderBy('gs.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopRated(int $limit = 10): array
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.isOpen = :isOpen')
            ->setParameter('isOpen', true)
            ->orderBy('gs.rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(GroceryStore $store): void
    {
        $this->getEntityManager()->persist($store);
        $this->getEntityManager()->flush();
    }
}
