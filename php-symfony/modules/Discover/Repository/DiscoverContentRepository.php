<?php

declare(strict_types=1);

namespace App\Discover\Repository;

use App\Discover\Entity\DiscoverContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DiscoverContent>
 */
class DiscoverContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscoverContent::class);
    }

    public function save(DiscoverContent $content): void
    {
        $this->getEntityManager()->persist($content);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?DiscoverContent
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findTrending(int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.viewCount', 'DESC')
            ->addOrderBy('d.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecommended(string $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function refreshRecommendationScores(): void
    {
        // Implementation for refreshing recommendation scores
    }

    public function findByType(string $type, int $limit = 20): array
    {
        return $this->findBy(['type' => $type], ['publishedAt' => 'DESC'], $limit);
    }

    public function findByQuery(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByQuery(string $query, ?string $type = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.title LIKE :query OR d.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        if ($type) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function incrementViewCount(string $contentId): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.viewCount', 'd.viewCount + 1')
            ->where('d.id = :id')
            ->setParameter('id', $contentId)
            ->getQuery()
            ->execute();
    }

    public function incrementLikeCount(string $contentId): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.likeCount', 'd.likeCount + 1')
            ->where('d.id = :id')
            ->setParameter('id', $contentId)
            ->getQuery()
            ->execute();
    }
}
