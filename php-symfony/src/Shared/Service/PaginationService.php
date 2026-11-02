<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;

/**
 * Pagination Service for Doctrine ORM
 */
class PaginationService
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 100;

    /**
     * Create paginated result
     */
    public function paginate(
        QueryBuilder $qb,
        int $page = self::DEFAULT_PAGE,
        int $limit = self::DEFAULT_LIMIT
    ): array {
        $page = max(1, $page);
        $limit = min(self::MAX_LIMIT, max(1, $limit));
        $offset = ($page - 1) * $limit;

        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $paginator = $this->createPaginator($qb);
        
        return [
            'data' => iterator_to_array($paginator->getIterator()),
            'meta' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $paginator->count(),
                'total_pages' => (int) ceil($paginator->count() / $limit),
            ],
        ];
    }

    /**
     * Create paginator - overridable for testing.
     */
    protected function createPaginator(QueryBuilder $qb): Paginator
    {
        return new Paginator($qb);
    }
}

/**
 * FastAPI-style pagination schema
 */
final readonly class PaginationSchema
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public int $totalItems = 0,
        public int $totalPages = 0,
    ) {}
}

// PHP 8.4 constructor property promotion
final class PaginatedResult
{
    public function __construct(
        public readonly array $data,
        public readonly PaginationSchema $pagination,
    ) {}
}
