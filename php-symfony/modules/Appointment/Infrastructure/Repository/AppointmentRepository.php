<?php

declare(strict_types=1);

namespace App\Appointment\Infrastructure\Repository;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Domain\Repository\AppointmentRepositoryInterface;

class AppointmentRepository extends EntityRepository implements AppointmentRepositoryInterface
{
    public function findById(string $id): ?Appointment
    {
        return $this->find($id);
    }

    public function findByNumber(string $number): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->where('a.appointmentNumber = :number')
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByWard(string $wardCode, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode);

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('a.appointmentDate', 'ASC')
           ->addOrderBy('a.appointmentTime', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByOfficer(string $officerId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.officerId = :officerId')
            ->setParameter('officerId', $officerId);

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('a.appointmentDate', 'ASC')
           ->addOrderBy('a.appointmentTime', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findByPhone(string $phone): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.citizenPhone = :phone')
            ->setParameter('phone', $phone)
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableSlots(string $wardCode, string $date): array
    {
        $dateObj = new \DateTimeImmutable($date);
        $occupiedSlots = $this->fetchOccupiedSlots($wardCode, $dateObj);
        $standardSlots = $this->getStandardSlots();

        $availableSlots = [];
        foreach ($standardSlots as $slot) {
            if ($this->isSlotAvailable($slot, $occupiedSlots)) {
                $availableSlots[] = $slot;
            }
        }

        return $availableSlots;
    }

    private function fetchOccupiedSlots(string $wardCode, \DateTimeImmutable $date): array
    {
        $allAppointments = $this->createQueryBuilder('a')
            ->where('a.wardCode = :wardCode')
            ->andWhere('a.appointmentDate = :date')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('wardCode', $wardCode)
            ->setParameter('date', $date)
            ->setParameter('statuses', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->getQuery()
            ->getResult();

        $occupied = [];
        foreach ($allAppointments as $appt) {
            $occupied[] = [
                'start' => $appt->getAppointmentTime(),
                'end' => $appt->getEndTime()->format('H:i'),
            ];
        }
        return $occupied;
    }

    private function getStandardSlots(): array
    {
        return [
            '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
            '11:00', '11:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30'
        ];
    }

    private function isSlotAvailable(string $slot, array $occupiedSlots): bool
    {
        $slotEnd = date('H:i', strtotime($slot) + 30 * 60);
        foreach ($occupiedSlots as $occupied) {
            if ($this->hasOverlap($slot, $slotEnd, $occupied['start'], $occupied['end'])) {
                return false;
            }
        }
        return true;
    }

    private function hasOverlap(string $slotStart, string $slotEnd, string $occStart, string $occEnd): bool
    {
        return ($slotStart >= $occStart && $slotStart < $occEnd) ||
               ($slotEnd > $occStart && $slotEnd <= $occEnd) ||
               ($slotStart <= $occStart && $slotEnd >= $occEnd);
    }

    public function findUpcomingAppointments(?string $officerId = null): array
    {
        $now = new DateTimeImmutable();
        
        $qb = $this->createQueryBuilder('a')
            ->where('a.appointmentDate >= :today')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('today', $now->format('Y-m-d'))
            ->setParameter('statuses', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC');

        if ($officerId !== null) {
            $qb->andWhere('a.officerId = :officerId')
               ->setParameter('officerId', $officerId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findTodayAppointments(string $wardCode, ?string $officerId = null): array
    {
        $today = new DateTimeImmutable('today');
        
        $qb = $this->createQueryBuilder('a')
            ->where('a.wardCode = :wardCode')
            ->andWhere('a.appointmentDate = :today')
            ->setParameter('wardCode', $wardCode)
            ->setParameter('today', $today)
            ->orderBy('a.appointmentTime', 'ASC');

        if ($officerId !== null) {
            $qb->andWhere('a.officerId = :officerId')
               ->setParameter('officerId', $officerId);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Appointment $appointment): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($appointment)) {
            $em->persist($appointment);
        }
        $em->flush();
    }

    public function remove(Appointment $appointment): void
    {
        $em = $this->getEntityManager();
        $em->remove($appointment);
        $em->flush();
    }

    public function getStatistics(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->buildStatisticsQuery($wardCode, $startDate, $endDate);
        $results = $qb->getQuery()->getResult();

        $stats = $this->initializeStats();
        $this->applyResultsToStats($stats, $results);

        $avgRating = $this->buildAverageRatingQuery($wardCode, $startDate, $endDate)
            ->getQuery()
            ->getSingleScalarResult();
        $stats['averageRating'] = $avgRating !== null ? (float)$avgRating : null;

        return $stats;
    }

    private function buildStatisticsQuery(string $wardCode, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.status', 'COUNT(a.id) as count')
            ->where('a.wardCode = :wardCode')
            ->setParameter('wardCode', $wardCode);

        if ($startDate !== null) {
            $qb->andWhere('a.appointmentDate >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        if ($endDate !== null) {
            $qb->andWhere('a.appointmentDate <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('a.status');
        return $qb;
    }

    private function buildAverageRatingQuery(string $wardCode, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('AVG(a.citizenRating) as avgRating')
            ->where('a.wardCode = :wardCode')
            ->andWhere('a.citizenRating IS NOT NULL')
            ->setParameter('wardCode', $wardCode);

        if ($startDate !== null) {
            $qb->andWhere('a.completedAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        if ($endDate !== null) {
            $qb->andWhere('a.completedAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb;
    }

    private function initializeStats(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'inProgress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'noShow' => 0,
        ];
    }

    private function applyResultsToStats(array &$stats, array $results): void
    {
        foreach ($results as $result) {
            $stats['total'] += $result['count'];
            switch ($result['status']) {
                case Appointment::STATUS_PENDING:
                    $stats['pending'] = $result['count'];
                    break;
                case Appointment::STATUS_CONFIRMED:
                    $stats['confirmed'] = $result['count'];
                    break;
                case Appointment::STATUS_IN_PROGRESS:
                    $stats['inProgress'] = $result['count'];
                    break;
                case Appointment::STATUS_COMPLETED:
                    $stats['completed'] = $result['count'];
                    break;
                case Appointment::STATUS_CANCELLED:
                    $stats['cancelled'] = $result['count'];
                    break;
                case Appointment::STATUS_NO_SHOW:
                    $stats['noShow'] = $result['count'];
                    break;
            }
        }
    }
}
