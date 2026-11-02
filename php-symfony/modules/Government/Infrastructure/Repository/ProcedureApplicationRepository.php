<?php declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Government\Domain\Entity\ProcedureApplication;
use App\Government\Domain\Repository\ProcedureApplicationRepositoryInterface;

class ProcedureApplicationRepository implements ProcedureApplicationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findById(string $id): ?ProcedureApplication
    {
        return $this->entityManager->find(ProcedureApplication::class, $id);
    }

    public function findByCitizen(string $citizenId): array
    {
        return $this->entityManager->getRepository(ProcedureApplication::class)
            ->findBy(['citizenId' => $citizenId], ['createdAt' => 'DESC']);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->entityManager->getRepository(ProcedureApplication::class)
            ->createQueryBuilder('a');

        if (!empty($filters['citizenId'])) {
            $qb->andWhere('a.citizenId = :citizenId')
                ->setParameter('citizenId', $filters['citizenId']);
        }

        if (!empty($filters['procedureId'])) {
            $qb->andWhere('a.procedureId = :procedureId')
                ->setParameter('procedureId', $filters['procedureId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['processedBy'])) {
            $qb->andWhere('a.processedBy = :processedBy')
                ->setParameter('processedBy', $filters['processedBy']);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function save(ProcedureApplication $application): void
    {
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    public function delete(string $id): bool
    {
        $application = $this->findById($id);
        if ($application) {
            $this->entityManager->remove($application);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(ProcedureApplication::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
