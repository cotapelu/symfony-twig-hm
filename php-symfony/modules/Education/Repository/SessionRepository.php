<?php declare(strict_types=1);
namespace App\Education\Repository;
use App\Education\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function findByStudent(string $studentId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.studentId = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTutor(int $tutorId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.tutor = :tutorId')
            ->setParameter('tutorId', $tutorId)
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', Session::STATUS_SCHEDULED)
            ->andWhere('s.scheduledAt > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Session $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }
}
