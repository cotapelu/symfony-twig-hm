<?php

declare(strict_types=1);

namespace App\Population\Repository;

use App\Population\Entity\PopulationRecord;
use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PopulationRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method PopulationRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method PopulationRecord[]    findAll()
 * @method PopulationRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PopulationRecordRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PopulationRecord::class);
    }

public function findPaginated(int $page, int $limit, array $filters = [], string $search = ''): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($filters as $field => $value) {
            if ($field === 'search') {
                continue;
            }
            $queryBuilder->andWhere("p.$field = :$field")
                ->setParameter($field, $value);
        }

        if ($search) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'p.name LIKE :search',
                    'p.address LIKE :search'
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $results = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map(fn(PopulationRecord $r) => $r->toArray(), $results),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit)
        ];
    }

    public function search(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        $results = $this->createQueryBuilder('p')
            ->where(
                $this->expr()->orX(
                    'p.name LIKE :query',
                    'p.address LIKE :query'
                )
            )
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        return array_map(fn(PopulationRecord $r) => $r->toArray(), $results);
    }
}