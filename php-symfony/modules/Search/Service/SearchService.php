<?php

declare(strict_types=1);

namespace App\Search\Service;

use App\Search\Entity\SearchHistory;
use App\Search\Entity\SearchResult;
use Doctrine\ORM\EntityManagerInterface;

class SearchService
{
    private const MAX_LIMIT = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function search(string $userId, string $query, ?string $entityType = null, int $limit = 20)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('sr')
            ->from(SearchResult::class, 'sr')
            ->where('sr.userId = :userId')
            ->andWhere('sr.query LIKE :query')
            ->setParameter('userId', $userId)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('sr.score', 'DESC')
            ->setMaxResults(min($limit, self::MAX_LIMIT));

        if ($entityType !== null) {
            $qb->andWhere('sr.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        return $qb->getQuery()->getResult();
    }

    public function saveSearchHistory(string $userId, string $query, int $resultCount)
    {
        $history = new SearchHistory();
        $history->setUserId($userId);
        $history->setQuery($query);
        $history->setResultCount($resultCount);

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $history;
    }

    public function getRecentSearches(string $userId, int $limit = 10)
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('sh')
            ->from(SearchHistory::class, 'sh')
            ->where('sh.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('sh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTrendingQueries(int $limit = 10)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $results = $qb->select('sh.query, COUNT(sh.id) as count')
            ->from(SearchHistory::class, 'sh')
            ->where('sh.createdAt > :date')
            ->setParameter('date', new \DateTime('-24 hours'))
            ->groupBy('sh.query')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => $r['query'], $results);
    }
}
