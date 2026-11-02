<?php

declare(strict_types=1);

namespace App\ANTT\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;

class IncidentRepository implements IncidentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(Incident $incident): void
    {
        $this->entityManager->persist($incident);
        $this->entityManager->flush();
    }

    public function find(int $id): ?Incident
    {
        return $this->entityManager->find(Incident::class, $id);
    }

    public function findById(string $id): ?Incident
    {
        return $this->entityManager->find(Incident::class, $id);
    }

    public function findByWard(string $wardCode): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['wardCode' => $wardCode], ['createdAt' => 'DESC']);
    }

    public function findByOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['assignedOfficerId' => $officerId], ['createdAt' => 'DESC']);
    }

    public function findUnresolved(): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['status' => ['reported', 'assigned', 'in_progress']], ['createdAt' => 'DESC']);
    }

    public function findOverdue(): array
    {
        // Entity doesn't have resolutionDeadline field, return empty array
        return [];
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByWard(string $wardCode): int
    {
        return (int) $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByIncidentNumber(string $number): ?Incident
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findOneBy(['incidentNumber' => $number]);
    }

    public function findByStatus(string $status): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function findByWardCode(string $wardCode): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['wardCode' => $wardCode], ['createdAt' => 'DESC']);
    }

    public function findByAssignedOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['assignedOfficerId' => $officerId], ['createdAt' => 'DESC']);
    }

    public function findByIncidentType(string $type): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['incidentType' => $type], ['createdAt' => 'DESC']);
    }

    public function findBySeverity(string $severity): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['severity' => $severity], ['createdAt' => 'DESC']);
    }

    public function findByReporterId(string $reporterId): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy(['reporterId' => $reporterId], ['createdAt' => 'DESC']);
    }

    public function findOpenIncidents(int $limit = 50, int $offset = 0): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->where('i.status IN (:statuses)')
            ->setParameter('statuses', ['reported', 'assigned', 'in_progress'])
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit): array
    {
        return $this->entityManager->getRepository(Incident::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    public function search(array $criteria): array
    {
        $qb = $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i');

        if (!empty($criteria['status'])) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('i.incidentType = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['wardCode'])) {
            $qb->andWhere('i.wardCode = :wardCode')
               ->setParameter('wardCode', $criteria['wardCode']);
        }

        if (!empty($criteria['severity'])) {
            $qb->andWhere('i.severity = :severity')
               ->setParameter('severity', $criteria['severity']);
        }

        if (!empty($criteria['assignedOfficerId'])) {
            $qb->andWhere('i.assignedOfficerId = :assignedOfficerId')
               ->setParameter('assignedOfficerId', $criteria['assignedOfficerId']);
        }

        if (isset($criteria['dateFrom'])) {
            $qb->andWhere('i.incidentDate >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if (isset($criteria['dateTo'])) {
            $qb->andWhere('i.incidentDate <= :dateTo')
               ->setParameter('dateTo', $criteria['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(): array
    {
        $results = $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('i.status, COUNT(i.id) as count')
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
        }
        return $counts;
    }

    public function countByType(): array
    {
        $results = $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('i.incidentType, COUNT(i.id) as count')
            ->groupBy('i.incidentType')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['incidentType']] = $row['count'];
        }
        return $counts;
    }

    public function countByTypeWithWard(string $wardCode): array
    {
        $results = $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('i.incidentType, COUNT(i.id) as count')
            ->andWhere('i.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode)
            ->groupBy('i.incidentType')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['incidentType']] = $row['count'];
        }
        return $counts;
    }

    public function countByWardCode(string $wardCode): array
    {
        $results = $this->entityManager->getRepository(Incident::class)
            ->createQueryBuilder('i')
            ->select('i.status, COUNT(i.id) as count')
            ->andWhere('i.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode)
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
        }
        return $counts;
    }

    public function delete(Incident $incident): void
    {
        $this->entityManager->remove($incident);
        $this->entityManager->flush();
    }
}
