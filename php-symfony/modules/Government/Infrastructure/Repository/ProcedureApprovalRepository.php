<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\ProcedureApproval;
use App\Government\Domain\Repository\ProcedureApprovalRepositoryInterface;

class ProcedureApprovalRepository implements ProcedureApprovalRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?ProcedureApproval
    {
        return $this->entityManager->find(ProcedureApproval::class, $id);
    }

    public function findByProcedureId(string $procedureId): array
    {
        $qb = $this->entityManager->getRepository(ProcedureApproval::class)
            ->createQueryBuilder('pa')
            ->where('pa.procedureId = :procedureId')
            ->setParameter('procedureId', $procedureId)
            ->orderBy('pa.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(ProcedureApproval::class)
            ->createQueryBuilder('pa');

        if (!empty($filters['procedureId'])) {
            $qb->andWhere('pa.procedureId = :procedureId')
                ->setParameter('procedureId', $filters['procedureId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('pa.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['processedBy'])) {
            $qb->andWhere('pa.processedBy = :processedBy')
                ->setParameter('processedBy', $filters['processedBy']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('pa.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function save(ProcedureApproval $approval): void
    {
        $this->entityManager->persist($approval);
        $this->entityManager->flush();
    }

    public function delete(string $id): bool
    {
        $approval = $this->findById($id);
        if ($approval) {
            $this->entityManager->remove($approval);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(ProcedureApproval::class)
            ->createQueryBuilder('pa')
            ->select('COUNT(pa.id)');

        if (!empty($filters['procedureId'])) {
            $qb->andWhere('pa.procedureId = :procedureId')
                ->setParameter('procedureId', $filters['procedureId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('pa.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['processedBy'])) {
            $qb->andWhere('pa.processedBy = :processedBy')
                ->setParameter('processedBy', $filters['processedBy']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function searchByTitle(string $keyword, int $limit = 20): array
    {
        // ProcedureApproval doesn't have a title, but we could join with Procedure if needed
        // For now, return empty array
        return [];
    }

    public function findByType(string $type, int $page = 1, int $perPage = 20): array
    {
        // ProcedureApproval doesn't have a type, delegate to procedure type if needed
        return [];
    }

    public function findByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(ProcedureApproval::class)
            ->createQueryBuilder('pa')
            ->where('pa.status = :status')
            ->setParameter('status', $status);

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('pa.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}