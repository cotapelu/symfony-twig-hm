<?php

declare(strict_types=1);

namespace App\Education\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Education\Entity\Course;

class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category = :category')
            ->setParameter('category', $category)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByInstructor(string $instructor): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.instructor = :instructor')
            ->setParameter('instructor', $instructor)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByLevel(string $level): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.level = :level')
            ->setParameter('level', $level)
            ->orderBy('c.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveCourses(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchByTitle(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.title LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Course $course): void
    {
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }
}
