<?php

declare(strict_types=1);

namespace App\ANTT\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\ANTT\Domain\Entity\SecurityCase;
use App\ANTT\Domain\Repository\SecurityCaseRepositoryInterface;

class SecurityCaseRepository implements SecurityCaseRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(SecurityCase $case): void
    {
        $this->entityManager->persist($case);
        $this->entityManager->flush();
    }

    public function find(int $id): ?SecurityCase
    {
        return $this->entityManager->find(SecurityCase::class, $id);
    }

    public function findById(string $id): ?SecurityCase
    {
        return $this->entityManager->find(SecurityCase::class, $id);
    }

    public function findByOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['assignedOfficerId' => $officerId], ['createdAt' => 'DESC']);
    }

    public function findOpen(): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['status' => 'open'], ['createdAt' => 'DESC']);
    }

    public function findUnresolved(): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s')
            ->andWhere('s.status NOT IN (:resolved, :closed)')
            ->setParameter('resolved', 'resolved')
            ->setParameter('closed', 'closed')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCaseNumber(string $number): ?SecurityCase
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findOneBy(['caseNumber' => $number]);
    }

    public function findByStatus(string $status): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function findByPriority(string $priority): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['priority' => $priority], ['createdAt' => 'DESC']);
    }

    public function findByCaseType(string $type): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['type' => $type], ['createdAt' => 'DESC']);
    }

    public function findByInvestigationOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['assignedOfficerId' => $officerId], ['createdAt' => 'DESC']);
    }

    public function findByWardCode(string $wardCode): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['wardId' => $wardCode], ['createdAt' => 'DESC']);
    }

    public function findByWard(string $wardId): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['wardId' => $wardId], ['createdAt' => 'DESC']);
    }

    public function findOpenCases(): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy(['status' => 'open'], ['createdAt' => 'DESC']);
    }

    public function findRecent(int $limit): array
    {
        return $this->entityManager->getRepository(SecurityCase::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    public function search(array $criteria): array
    {
        $qb = $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s');

        if (!empty($criteria['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('s.type = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['priority'])) {
            $qb->andWhere('s.priority = :priority')
               ->setParameter('priority', $criteria['priority']);
        }

        if (!empty($criteria['assignedOfficerId'])) {
            $qb->andWhere('s.assignedOfficerId = :assignedOfficerId')
               ->setParameter('assignedOfficerId', $criteria['assignedOfficerId']);
        }

        if (!empty($criteria['wardId'])) {
            $qb->andWhere('s.wardId = :wardId')
               ->setParameter('wardId', $criteria['wardId']);
        }

        if (!empty($criteria['relatedIncidentId'])) {
            $qb->andWhere('s.relatedIncidentId = :relatedIncidentId')
               ->setParameter('relatedIncidentId', $criteria['relatedIncidentId']);
        }

        if (isset($criteria['dateFrom'])) {
            $qb->andWhere('s.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if (isset($criteria['dateTo'])) {
            $qb->andWhere('s.createdAt <= :dateTo')
               ->setParameter('dateTo', $criteria['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(): array
    {
        $results = $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->groupBy('s.status')
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
        $results = $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s')
            ->select('s.type, COUNT(s.id) as count')
            ->groupBy('s.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['type']] = $row['count'];
        }
        return $counts;
    }

    public function countByPriority(): array
    {
        $results = $this->entityManager->getRepository(SecurityCase::class)
            ->createQueryBuilder('s')
            ->select('s.priority, COUNT(s.id) as count')
            ->groupBy('s.priority')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['priority']] = $row['count'];
        }
        return $counts;
    }

    public function delete(SecurityCase $case): void
    {
        $this->entityManager->remove($case);
        $this->entityManager->flush();
    }
}
