<?php

declare(strict_types=1);

namespace App\Education\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Education\Entity\Lesson;

class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function findByCourse(int $courseId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('l.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(int $courseId, string $type): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.course = :courseId')
            ->andWhere('l.type = :type')
            ->setParameter('courseId', $courseId)
            ->setParameter('type', $type)
            ->orderBy('l.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Lesson $lesson): void
    {
        $this->getEntityManager()->persist($lesson);
        $this->getEntityManager()->flush();
    }

    public function delete(Lesson $lesson): void
    {
        $this->getEntityManager()->remove($lesson);
        $this->getEntityManager()->flush();
    }
}
