<?php

declare(strict_types=1);

namespace App\Utility\Repository;

use App\Utility\Entity\Workflow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkflowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workflow::class);
    }

    public function save(Workflow $workflow): void
    {
        $this->getEntityManager()->persist($workflow);
        $this->getEntityManager()->flush();
    }

    public function delete(Workflow $workflow): void
    {
        $this->getEntityManager()->remove($workflow);
        $this->getEntityManager()->flush();
    }

    public function findByName(string $name): ?Workflow
    {
        return $this->createQueryBuilder('w')
            ->where('w.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', $status)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEntityType(string $entityType): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.entityType = :entityType')
            ->setParameter('entityType', $entityType)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEntityId(int $entityId): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.entityId = :entityId')
            ->setParameter('entityId', $entityId)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->createQueryBuilder('w')
            ->update()
            ->set('w.status', ':status')
            ->where('w.id = :id')
            ->setParameter('status', $status)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}