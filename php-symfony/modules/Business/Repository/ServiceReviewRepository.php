<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Business\Entity\ServiceReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceReview::class);
    }

    public function save(ServiceReview $review): void
    {
        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();
    }

    public function delete(ServiceReview $review): void
    {
        $this->getEntityManager()->remove($review);
        $this->getEntityManager()->flush();
    }

    public function findByCustomerId(int $customerId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBusinessId(int $businessId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.businessId = :businessId')
            ->setParameter('businessId', $businessId)
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBookingId(int $bookingId): ?ServiceReview
    {
        return $this->createQueryBuilder('r')
            ->where('r.bookingId = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRating(int $rating): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.rating = :rating')
            ->setParameter('rating', $rating)
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function calculateAverageRating(int $businessId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->where('r.businessId = :businessId')
            ->setParameter('businessId', $businessId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function countReviews(int $businessId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.businessId = :businessId')
            ->setParameter('businessId', $businessId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}