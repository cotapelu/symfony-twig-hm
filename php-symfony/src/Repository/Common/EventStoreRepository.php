<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\EventStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<EventStore>
 */
class EventStoreRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventStore::class);
    }




    public function findByAggregateId(string $aggregateId): array
    {
        return $this->findBy(['aggregateId' => $aggregateId], ['version' => 'ASC']);
    }

    public function findByAggregateIdAndVersion(string $aggregateId, int $version): ?EventStore
    {
        return $this->findOneBy(['aggregateId' => $aggregateId, 'version' => $version]);
    }

    public function findLatestVersion(string $aggregateId): ?EventStore
    {
        return $this->createQueryBuilder('e')
            ->where('e.aggregateId = :aggregateId')
            ->orderBy('e.version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('aggregateId', $aggregateId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentEvents(int $limit = 100): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.createdAt >= :start')
            ->andWhere('e.createdAt <= :end')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function countByAggregateId(string $aggregateId): int
    {
        return $this->count(['aggregateId' => $aggregateId]);
    }

    public function getMaxVersion(string $aggregateId): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.version)')
            ->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}
