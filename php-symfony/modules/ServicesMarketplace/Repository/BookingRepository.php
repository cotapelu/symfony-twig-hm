<?php declare(strict_types=1);
namespace App\ServicesMarketplace\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ServicesMarketplace\Entity\Booking;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByProvider(int $providerId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.provider = :providerId')
            ->setParameter('providerId', $providerId)
            ->orderBy('b.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveBookings(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('statuses', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_IN_PROGRESS
            ])
            ->orderBy('b.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingBookings(int $providerId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.provider = :providerId')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('b.scheduledAt > :now')
            ->setParameter('providerId', $providerId)
            ->setParameter('statuses', [Booking::STATUS_CONFIRMED])
            ->setParameter('now', new \DateTime())
            ->orderBy('b.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Booking $booking): void
    {
        $this->getEntityManager()->persist($booking);
        $this->getEntityManager()->flush();
    }

    public function updateStatus(int $bookingId, string $status): void
    {
        $this->createQueryBuilder('b')
            ->update(Booking::class, 'b')
            ->set('b.status', ':status')
            ->where('b.id = :id')
            ->setParameter('status', $status)
            ->setParameter('id', $bookingId)
            ->getQuery()
            ->execute();

        if ($status === Booking::STATUS_COMPLETED) {
            $this->createQueryBuilder('b')
                ->update(Booking::class, 'b')
                ->set('b.completedAt', ':completedAt')
                ->where('b.id = :id')
                ->setParameter('completedAt', new \DateTime())
                ->setParameter('id', $bookingId)
                ->getQuery()
                ->execute();
        }
    }
}
