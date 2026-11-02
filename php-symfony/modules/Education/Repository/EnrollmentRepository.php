<?php

declare(strict_types=1);

namespace App\Education\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Education\Entity\Enrollment;

class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCourseId(int $courseId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndCourse(string $userId, int $courseId): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->andWhere('e.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveEnrollments(string $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->andWhere('e.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'active')
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Enrollment $enrollment): void
    {
        $this->getEntityManager()->persist($enrollment);
        $this->getEntityManager()->flush();
    }

    public function delete(Enrollment $enrollment): void
    {
        $this->getEntityManager()->remove($enrollment);
        $this->getEntityManager()->flush();
    }
}
