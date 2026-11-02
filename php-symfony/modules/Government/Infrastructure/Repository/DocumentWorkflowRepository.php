<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\DocumentWorkflow;
use App\Government\Domain\Repository\DocumentWorkflowRepositoryInterface;

class DocumentWorkflowRepository implements DocumentWorkflowRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?DocumentWorkflow
    {
        return $this->entityManager->find(DocumentWorkflow::class, $id);
    }

    public function findByDocumentId(string $documentId): array
    {
        $qb = $this->entityManager->getRepository(DocumentWorkflow::class)
            ->createQueryBuilder('dw')
            ->where('dw.documentId = :documentId')
            ->setParameter('documentId', $documentId)
            ->orderBy('dw.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(DocumentWorkflow::class)
            ->createQueryBuilder('dw');

        if (!empty($filters['documentId'])) {
            $qb->andWhere('dw.documentId = :documentId')
                ->setParameter('documentId', $filters['documentId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('dw.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['processedBy'])) {
            $qb->andWhere('dw.processedBy = :processedBy')
                ->setParameter('processedBy', $filters['processedBy']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('dw.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function save(DocumentWorkflow $workflow): void
    {
        $this->entityManager->persist($workflow);
        $this->entityManager->flush();
    }

    public function delete(string $id): bool
    {
        $workflow = $this->findById($id);
        if ($workflow) {
            $this->entityManager->remove($workflow);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(DocumentWorkflow::class)
            ->createQueryBuilder('dw')
            ->select('COUNT(dw.id)');

        if (!empty($filters['documentId'])) {
            $qb->andWhere('dw.documentId = :documentId')
                ->setParameter('documentId', $filters['documentId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('dw.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['processedBy'])) {
            $qb->andWhere('dw.processedBy = :processedBy')
                ->setParameter('processedBy', $filters['processedBy']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function searchByTitle(string $keyword, int $limit = 20): array
    {
        // DocumentWorkflow doesn't have a title, but we could join with Document if needed
        // For now, return empty array
        return [];
    }

    public function findByType(string $type, int $page = 1, int $perPage = 20): array
    {
        // DocumentWorkflow doesn't have a type, delegate to document type if needed
        return [];
    }

    public function findByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(DocumentWorkflow::class)
            ->createQueryBuilder('dw')
            ->where('dw.status = :status')
            ->setParameter('status', $status);

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('dw.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}