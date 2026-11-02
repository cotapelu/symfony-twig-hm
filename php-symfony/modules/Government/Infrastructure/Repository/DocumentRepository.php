<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\Document;
use App\Government\Domain\Repository\DocumentRepositoryInterface;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?Document
    {
        return $this->entityManager->find(Document::class, $id);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d');

        if (!empty($filters['search'])) {
            $qb->andWhere('d.title LIKE :search OR d.content LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['type'])) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $filters['status']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage)
           ->orderBy('d.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function save(Document $document): void
    {
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    public function delete(string $id): bool
    {
        $document = $this->findById($id);
        if ($document) {
            $this->entityManager->remove($document);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)');

        if (isset($filters['type'])) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $filters['status']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function searchByTitle(string $keyword, int $limit = 20): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('d')
            ->andWhere('d.title LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->setMaxResults($limit)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type, int $page = 1, int $perPage = 20): array
    {
        return $this->findAll(['type' => $type], $page, $perPage);
    }

    public function findByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        return $this->findAll(['status' => $status], $page, $perPage);
    }
}
