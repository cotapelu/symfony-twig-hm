<?php

declare(strict_types=1);

namespace App\JobFinding\Repository;

use App\JobFinding\Entity\JobApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    public function findByJob(int $jobId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.jobId = :jobId')
            ->setParameter('jobId', $jobId)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByApplicant(int $applicantId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.applicantId = :applicantId')
            ->setParameter('applicantId', $applicantId)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(JobApplication $application): void
    {
        $this->getEntityManager()->persist($application);
        $this->getEntityManager()->flush();
    }

    public function delete(JobApplication $application): void
    {
        $this->getEntityManager()->remove($application);
        $this->getEntityManager()->flush();
    }

    public function countByJob(int $jobId): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.jobId = :jobId')
            ->setParameter('jobId', $jobId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
