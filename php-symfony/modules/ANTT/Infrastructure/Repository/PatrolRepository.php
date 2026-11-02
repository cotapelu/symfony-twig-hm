<?php

declare(strict_types=1);

namespace App\ANTT\Infrastructure\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\ANTT\Domain\Entity\Patrol;
use App\ANTT\Domain\Repository\PatrolRepositoryInterface;

class PatrolRepository implements PatrolRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(Patrol $patrol): void
    {
        $this->entityManager->persist($patrol);
        $this->entityManager->flush();
    }

    public function find(int $id): ?Patrol
    {
        return $this->entityManager->find(Patrol::class, $id);
    }

    public function findById(string $id): ?Patrol
    {
        return $this->entityManager->find(Patrol::class, $id);
    }

    public function findByWard(string $wardId): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['wardCode' => $wardId], ['patrolDate' => 'DESC']);
    }

    public function findByOfficer(string $officerId): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['teamLeaderId' => $officerId], ['patrolDate' => 'DESC']);
    }

    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->andWhere('p.patrolDate >= :start')
            ->andWhere('p.patrolDate <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.patrolDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findScheduled(): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['status' => 'scheduled'], ['patrolDate' => 'ASC']);
    }

    public function findInProgress(): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['status' => 'in_progress'], ['patrolDate' => 'DESC']);
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByOfficer(string $officerId): int
    {
        return (int) $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.teamLeaderId = :officerId')
            ->setParameter('officerId', $officerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByWard(string $wardId): int
    {
        return (int) $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.wardCode = :wardId')
            ->setParameter('wardId', $wardId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByPatrolNumber(string $number): ?Patrol
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findOneBy(['patrolNumber' => $number]);
    }

    public function findByTeamId(string $teamId): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['teamId' => $teamId], ['patrolDate' => 'DESC']);
    }

    public function findByStatus(string $status): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['status' => $status], ['patrolDate' => 'DESC']);
    }

    public function findByDate(\DateTimeImmutable $date): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['patrolDate' => $date], ['startTime' => 'ASC']);
    }

    public function findByWardCode(string $wardCode): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['wardCode' => $wardCode], ['patrolDate' => 'DESC']);
    }

    public function findUpcoming(int $limit): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy(['status' => 'scheduled'], ['patrolDate' => 'ASC'], $limit);
    }

    public function findRecent(int $limit): array
    {
        return $this->entityManager->getRepository(Patrol::class)
            ->findBy([], ['patrolDate' => 'DESC'], $limit);
    }

    public function search(array $criteria): array
    {
        $qb = $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p');

        if (!empty($criteria['officerId'])) {
            $qb->andWhere('p.teamLeaderId = :officerId')
               ->setParameter('officerId', $criteria['officerId']);
        }

        if (!empty($criteria['wardId'])) {
            $qb->andWhere('p.wardCode = :wardId')
               ->setParameter('wardId', $criteria['wardId']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['teamId'])) {
            $qb->andWhere('p.teamId = :teamId')
               ->setParameter('teamId', $criteria['teamId']);
        }

        if (isset($criteria['dateFrom'])) {
            $qb->andWhere('p.patrolDate >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if (isset($criteria['dateTo'])) {
            $qb->andWhere('p.patrolDate <= :dateTo')
               ->setParameter('dateTo', $criteria['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(): array
    {
        $results = $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
        }
        return $counts;
    }

    public function countByTeam(): array
    {
        $results = $this->entityManager->getRepository(Patrol::class)
            ->createQueryBuilder('p')
            ->select('p.teamId, COUNT(p.id) as count')
            ->groupBy('p.teamId')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['teamId']] = $row['count'];
        }
        return $counts;
    }

    public function delete(Patrol $patrol): void
    {
        $this->entityManager->remove($patrol);
        $this->entityManager->flush();
    }
}
