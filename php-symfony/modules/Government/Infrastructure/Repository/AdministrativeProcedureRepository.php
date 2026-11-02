<?php declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\AdministrativeProcedure;
use App\Government\Domain\Repository\AdministrativeProcedureRepositoryInterface;

class AdministrativeProcedureRepository implements AdministrativeProcedureRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?AdministrativeProcedure
    {
        return $this->entityManager->find(AdministrativeProcedure::class, $id);
    }

    public function findByCode(string $code): ?AdministrativeProcedure
    {
        return $this->entityManager->getRepository(AdministrativeProcedure::class)
            ->findOneBy(['code' => $code]);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(AdministrativeProcedure::class)
            ->createQueryBuilder('p');

        if (!empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.code LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('p.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function save(AdministrativeProcedure $procedure): void
    {
        $this->entityManager->persist($procedure);
        $this->entityManager->flush();
    }

    public function delete(string $id): bool
    {
        $procedure = $this->findById($id);
        if ($procedure) {
            $this->entityManager->remove($procedure);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(AdministrativeProcedure::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
