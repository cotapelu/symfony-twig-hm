<?php

declare(strict_types=1);

namespace App\JobFinding\Repository;

use App\JobFinding\Entity\JobPosting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobPostingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobPosting::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $keyword, ?string $location = null, ?string $jobType = null, ?string $experienceLevel = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('(j.title LIKE :keyword OR j.description LIKE :keyword)')
            ->setParameter('keyword', '%' . $keyword . '%');

        if ($location) {
            $qb->andWhere('j.location LIKE :location')
               ->setParameter('location', '%' . $location . '%');
        }

        if ($jobType) {
            $qb->andWhere('j.jobType = :jobType')
               ->setParameter('jobType', $jobType);
        }

        if ($experienceLevel) {
            $qb->andWhere('j.experienceLevel = :experienceLevel')
               ->setParameter('experienceLevel', $experienceLevel);
        }

        return $qb->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmployer(string $employerId): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.employerId = :employerId')
            ->setParameter('employerId', $employerId)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->andWhere('j.location LIKE :location')
            ->setParameter('status', 'active')
            ->setParameter('location', '%' . $location . '%')
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(JobPosting $jobPosting): void
    {
        $this->getEntityManager()->persist($jobPosting);
        $this->getEntityManager()->flush();
    }

    public function delete(JobPosting $jobPosting): void
    {
        $this->getEntityManager()->remove($jobPosting);
        $this->getEntityManager()->flush();
    }

    public function incrementViewCount(int $id): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.viewCount', 'j.viewCount + 1')
            ->set('j.updatedAt', ':now')
            ->set('j.version', 'j.version + 1')
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function incrementApplicationCount(int $id): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.applicationCount', 'j.applicationCount + 1')
            ->set('j.updatedAt', ':now')
            ->set('j.version', 'j.version + 1')
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
