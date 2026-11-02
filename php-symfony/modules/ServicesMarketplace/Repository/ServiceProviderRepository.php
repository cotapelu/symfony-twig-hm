<?php declare(strict_types=1);
namespace App\ServicesMarketplace\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ServicesMarketplace\Entity\ServiceProvider;

class ServiceProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceProvider::class);
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.category = :category')
            ->andWhere('sp.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', 'approved')
            ->orderBy('sp.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm, int $limit = 20): array
    {
        $earthRadius = 6371;
        $latRange = $radiusKm / $earthRadius;
        $lonRange = $radiusKm / ($earthRadius * cos(deg2rad($latitude)));

        return $this->createQueryBuilder('sp')
            ->andWhere('sp.latitude BETWEEN :minLat AND :maxLat')
            ->andWhere('sp.longitude BETWEEN :minLon AND :maxLon')
            ->andWhere('sp.status = :status')
            ->setParameter('minLat', $latitude - $latRange)
            ->setParameter('maxLat', $latitude + $latRange)
            ->setParameter('minLon', $longitude - $lonRange)
            ->setParameter('maxLon', $longitude + $lonRange)
            ->setParameter('status', 'approved')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(string $userId): ?ServiceProvider
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.businessName LIKE :search OR sp.displayName LIKE :search')
            ->andWhere('sp.status = :status')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->setParameter('status', 'approved')
            ->orderBy('sp.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopRated(int $limit = 10): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.status = :status')
            ->setParameter('status', 'approved')
            ->orderBy('sp.rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.status = :status')
            ->setParameter('status', $status)
            ->orderBy('sp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ServiceProvider $provider): void
    {
        $this->getEntityManager()->persist($provider);
        $this->getEntityManager()->flush();
    }

    public function updateRating(int $providerId, float $rating, int $reviewCount): void
    {
        $this->createQueryBuilder('sp')
            ->update(ServiceProvider::class, 'sp')
            ->set('sp.rating', ':rating')
            ->set('sp.reviewCount', ':reviewCount')
            ->setParameter('rating', $rating)
            ->setParameter('reviewCount', $reviewCount)
            ->where('sp.id = :id')
            ->setParameter('id', $providerId)
            ->getQuery()
            ->execute();
    }
}
