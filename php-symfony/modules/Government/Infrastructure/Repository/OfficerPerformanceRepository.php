<?php

declare(strict_types=1);

namespace App\Government\Infrastructure\Repository;

use App\Government\Domain\Entity\OfficerPerformance;
use App\Government\Domain\Repository\OfficerPerformanceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class OfficerPerformanceRepository implements OfficerPerformanceRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findById(string $id): ?OfficerPerformance
    {
        return $this->entityManager->find(OfficerPerformance::class, $id);
    }

    public function findByOfficerIdAndMetricTypeAndTimePeriod(string $officerId, string $metricType, string $timePeriod): ?OfficerPerformance
    {
        return $this->entityManager->getRepository(OfficerPerformance::class)
            ->findOneBy([
                'officerId' => $officerId,
                'metricType' => $metricType,
                'timePeriod' => $timePeriod
            ]);
    }

    public function findByOfficerId(string $officerId, int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(OfficerPerformance::class)
            ->findBy(['officerId' => $officerId], null, $perPage, ($page - 1) * $perPage);
    }

    public function findByMetricType(string $metricType, int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(OfficerPerformance::class)
            ->findBy(['metricType' => $metricType], null, $perPage, ($page - 1) * $perPage);
    }

    public function save(OfficerPerformance $performance): void
    {
        $this->entityManager->persist($performance);
        $this->entityManager->flush();
    }

    public function delete(OfficerPerformance $performance): void
    {
        $this->entityManager->remove($performance);
        $this->entityManager->flush();
    }

    public function count(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(OfficerPerformance::class)
            ->createQueryBuilder('op')
            ->select('COUNT(op.id)');

        if (!empty($filters['officerId'])) {
            $qb->andWhere('op.officerId = :officerId')
                ->setParameter('officerId', $filters['officerId']);
        }

        if (!empty($filters['metricType'])) {
            $qb->andWhere('op.metricType = :metricType')
                ->setParameter('metricType', $filters['metricType']);
        }

        if (!empty($filters['timePeriod'])) {
            $qb->andWhere('op.timePeriod = :timePeriod')
                ->setParameter('timePeriod', $filters['timePeriod']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}