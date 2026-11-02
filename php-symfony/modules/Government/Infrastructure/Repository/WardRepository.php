<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\Ward;
use App\Government\Domain\Repository\WardRepositoryInterface;

class WardRepository implements WardRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?Ward
    {
        return $this->entityManager->find(Ward::class, $id);
    }

    public function findByCode(string $code): ?Ward
    {
        return $this->entityManager->getRepository(Ward::class)
            ->findOneBy(['code' => $code]);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(Ward::class)
            ->createQueryBuilder('w');

        if (!empty($filters['search'])) {
            $qb->andWhere('w.name LIKE :search OR w.code LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['province'])) {
            $qb->andWhere('w.province = :province')
               ->setParameter('province', $filters['province']);
        }

        if (!empty($filters['district'])) {
            $qb->andWhere('w.district = :district')
               ->setParameter('district', $filters['district']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage)
           ->orderBy('w.province', 'ASC')
           ->addOrderBy('w.district', 'ASC')
           ->addOrderBy('w.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function save(Ward $ward): void
    {
        $this->entityManager->persist($ward);
        $this->entityManager->flush();
    }

    public function delete(string $id): void
    {
        $ward = $this->findById($id);
        if ($ward) {
            $this->entityManager->remove($ward);
            $this->entityManager->flush();
        }
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Ward::class)
            ->createQueryBuilder('w')
            ->select('COUNT(w.id)');

        if (!empty($filters['province'])) {
            $qb->andWhere('w.province = :province')
               ->setParameter('province', $filters['province']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByAssignedOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(Ward::class)
            ->findBy(['assignedOfficerId' => $officerId]);
    }

    public function countByAssignedOfficer(string $officerId): int
    {
        $qb = $this->entityManager->getRepository(Ward::class)
            ->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.assignedOfficerId = :officerId')
            ->setParameter('officerId', $officerId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
