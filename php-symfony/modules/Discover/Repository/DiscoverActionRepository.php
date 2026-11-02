<?php

declare(strict_types=1);

namespace App\Discover\Repository;

use App\Discover\Entity\DiscoverAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DiscoverAction>
 */
class DiscoverActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscoverAction::class);
    }

    public function save(DiscoverAction $action): void
    {
        $this->getEntityManager()->persist($action);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?DiscoverAction
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByUser(string $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByContent(string $contentId, string $actionType = 'view', int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.contentId = :contentId')
            ->andWhere('a.actionType = :actionType')
            ->setParameter('contentId', $contentId)
            ->setParameter('actionType', $actionType)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByActionType(string $actionType): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldActions(\DateTimeImmutable $before): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
