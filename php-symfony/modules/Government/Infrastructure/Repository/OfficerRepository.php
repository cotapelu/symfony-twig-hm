<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use App\Government\Domain\ValueObject\OfficerId;

class OfficerRepository implements OfficerRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(OfficerId $id): ?Officer
    {
        return $this->entityManager->find(Officer::class, $id->getValue());
    }

    public function findByEmail(string $email): ?Officer
    {
        return $this->entityManager->getRepository(Officer::class)
            ->findOneBy(['email' => $email]);
    }

    public function findByIdentityNumber(string $identityNumber): ?Officer
    {
        return $this->entityManager->getRepository(Officer::class)
            ->findOneBy(['identityNumber' => $identityNumber]);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(Officer::class)
            ->createQueryBuilder('o');

        if (!empty($filters['search'])) {
            $qb->andWhere('o.fullName LIKE :search OR o.email LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['isActive'])) {
            $qb->andWhere('o.isActive = :isActive')
               ->setParameter('isActive', $filters['isActive']);
        }

        if (!empty($filters['departmentCode'])) {
            $qb->andWhere('o.department.code = :deptCode')
               ->setParameter('deptCode', $filters['departmentCode']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage)
           ->orderBy('o.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function save(Officer $officer): void
    {
        $this->entityManager->persist($officer);
        $this->entityManager->flush();
    }

    public function delete(OfficerId $id): void
    {
        $officer = $this->findById($id);
        if ($officer) {
            $this->entityManager->remove($officer);
            $this->entityManager->flush();
        }
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Officer::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)');

        if (isset($filters['isActive'])) {
            $qb->andWhere('o.isActive = :isActive')
               ->setParameter('isActive', $filters['isActive']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
