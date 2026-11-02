<?php

declare(strict_types=1);

namespace App\Feedback\Infrastructure\Repository;

use Doctrine\ORM\EntityRepository;
use App\Feedback\Domain\Entity\Feedback;
use App\Feedback\Domain\Repository\FeedbackRepositoryInterface;

class FeedbackRepository extends EntityRepository implements FeedbackRepositoryInterface
{
    public function findById(string $id): ?Feedback
    {
        return $this->find($id);
    }

    public function findByNumber(string $number): ?Feedback
    {
        return $this->createQueryBuilder('f')
            ->where('f.feedbackNumber = :number')
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByWard(string $wardCode, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode);

        if ($status !== null) {
            $qb->andWhere('f.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('f.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCitizen(string $citizenId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.citizen', 'c')
            ->where('c.id = :citizenId')
            ->setParameter('citizenId', $citizenId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPhone(string $phone): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.citizenPhone = :phone')
            ->setParameter('phone', $phone)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingFeedback(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.status IN (:statuses)')
            ->setParameter('statuses', [Feedback::STATUS_SUBMITTED, Feedback::STATUS_UNDER_REVIEW, Feedback::STATUS_IN_PROGRESS])
            ->orderBy('f.priority', 'DESC')
            ->addOrderBy('f.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueFeedback(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('f')
            ->where('f.responseDeadline IS NOT NULL')
            ->andWhere('f.responseDeadline < :now')
            ->andWhere('f.status NOT IN (:completedStatuses)')
            ->setParameter('now', $now)
            ->setParameter('completedStatuses', [Feedback::STATUS_RESOLVED, Feedback::STATUS_CLOSED, Feedback::STATUS_REJECTED])
            ->orderBy('f.responseDeadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Feedback $feedback): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($feedback)) {
            $em->persist($feedback);
        }
        $em->flush();
    }

    public function remove(Feedback $feedback): void
    {
        $em = $this->getEntityManager();
        $em->remove($feedback);
        $em->flush();
    }

    public function getStatistics(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $statusCounts = $this->fetchStatusCounts($wardCode, $startDate, $endDate);
        $stats = $this->initializeStats();
        $this->applyStatusCounts($stats, $statusCounts);
        $stats['pending'] = $this->fetchPendingCount($wardCode, $startDate, $endDate);
        return $stats;
    }

    private function fetchStatusCounts(string $wardCode, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.status', 'COUNT(f.id) as count')
            ->where('f.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode);

        if ($startDate !== null) {
            $qb->andWhere('f.createdAt >= :startDate')->setParameter('startDate', $startDate);
        }
        if ($endDate !== null) {
            $qb->andWhere('f.createdAt <= :endDate')->setParameter('endDate', $endDate);
        }

        $qb->groupBy('f.status');
        return $qb->getQuery()->getResult();
    }

    private function initializeStats(): array
    {
        return [
            'total' => 0,
            'submitted' => 0,
            'underReview' => 0,
            'inProgress' => 0,
            'resolved' => 0,
            'closed' => 0,
            'rejected' => 0,
        ];
    }

    private function applyStatusCounts(array &$stats, array $results): void
    {
        foreach ($results as $result) {
            $stats['total'] += $result['count'];
            switch ($result['status']) {
                case Feedback::STATUS_SUBMITTED:
                    $stats['submitted'] = $result['count'];
                    break;
                case Feedback::STATUS_UNDER_REVIEW:
                    $stats['underReview'] = $result['count'];
                    break;
                case Feedback::STATUS_IN_PROGRESS:
                    $stats['inProgress'] = $result['count'];
                    break;
                case Feedback::STATUS_RESOLVED:
                    $stats['resolved'] = $result['count'];
                    break;
                case Feedback::STATUS_CLOSED:
                    $stats['closed'] = $result['count'];
                    break;
                case Feedback::STATUS_REJECTED:
                    $stats['rejected'] = $result['count'];
                    break;
            }
        }
    }

    private function fetchPendingCount(string $wardCode, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.wardCode = :wardCode')
            ->andWhere('f.status IN (:statuses)')
            ->setParameter('wardCode', $wardCode)
            ->setParameter('statuses', [Feedback::STATUS_SUBMITTED, Feedback::STATUS_UNDER_REVIEW, Feedback::STATUS_IN_PROGRESS]);

        if ($startDate !== null) {
            $qb->andWhere('f.createdAt >= :startDate')->setParameter('startDate', $startDate);
        }
        if ($endDate !== null) {
            $qb->andWhere('f.createdAt <= :endDate')->setParameter('endDate', $endDate);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getAverageRating(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): ?float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('AVG(f.rating) as avgRating')
            ->where('f.wardCode = :wardCode')
            ->andWhere('f.rating IS NOT NULL')
            ->setParameter('wardCode', $wardCode);

        if ($startDate !== null) {
            $qb->andWhere('f.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('f.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? (float)$result : null;
    }

    public function getDepartmentBreakdown(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.department, COUNT(f.id) as count, AVG(f.rating) as avgRating')
            ->where('f.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode)
            ->groupBy('f.department')
            ->orderBy('count', 'DESC');

        if ($startDate !== null) {
            $qb->andWhere('f.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('f.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(fn($r) => [
            'department' => $r['department'] ?? 'Không phân loại',
            'count' => (int)$r['count'],
            'avgRating' => $r['avgRating'] !== null ? (float)$r['avgRating'] : null,
        ], $results);
    }
}
