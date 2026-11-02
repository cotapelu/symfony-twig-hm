<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\ServiceReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<ServiceReview>
 */
class ServiceReviewRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceReview::class);
    }




    public function findByBusinessId(string $businessId): array
    {
        return $this->findBy(['businessId' => $businessId], ['createdAt' => 'DESC']);
    }

    public function findByCustomerId(string $customerId): array
    {
        return $this->findBy(['customerId' => $customerId], ['createdAt' => 'DESC']);
    }

    public function findByBookingId(string $bookingId): array
    {
        return $this->findBy(['bookingId' => $bookingId]);
    }

    public function findByRating(int $rating): array
    {
        return $this->findBy(['rating' => $rating]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findPublishedReviews(): array
    {
        return $this->findBy(['status' => 'PUBLISHED']);
    }

    public function findHiddenReviews(): array
    {
        return $this->findBy(['status' => 'HIDDEN']);
    }

    public function findDeletedReviews(): array
    {
        return $this->findBy(['status' => 'DELETED']);
    }

    public function findPositiveReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.rating >= 4')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getResult();
    }

    public function findNeutralReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.rating = 3')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getResult();
    }

    public function findNegativeReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.rating <= 2')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getResult();
    }

    public function findRecentReviews(int $limit = 100): array
    {
        return $this->findBy(['status' => 'PUBLISHED'], ['createdAt' => 'DESC'], $limit);
    }

    public function countByBusinessId(string $businessId): int
    {
        return $this->count(['businessId' => $businessId]);
    }

    public function countByCustomerId(string $customerId): int
    {
        return $this->count(['customerId' => $customerId]);
    }

    public function countByRating(int $rating): int
    {
        return $this->count(['rating' => $rating]);
    }

    public function getAverageRatingByBusinessId(string $businessId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->where('r.businessId = :businessId')
            ->andWhere('r.status = :status')
            ->setParameter('businessId', $businessId)
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getTotalReviewsByBusinessId(string $businessId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.businessId = :businessId')
            ->andWhere('r.status = :status')
            ->setParameter('businessId', $businessId)
            ->setParameter('status', 'PUBLISHED')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
