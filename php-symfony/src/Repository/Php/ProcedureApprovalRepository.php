<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\ProcedureApproval;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProcedureApprovalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcedureApproval::class);
    }

    public function findByApplicationId(string $applicationId): array
    {
        return $this->findBy(['applicationId' => $applicationId]);
    }

    public function findByOfficerId(string $officerId): array
    {
        return $this->findBy(['officerId' => $officerId]);
    }

    public function findByDecision(string $decision): array
    {
        return $this->findBy(['decision' => $decision]);
    }

    public function findLatestByApplicationId(string $applicationId): ?ProcedureApproval
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.applicationId = :applicationId')
            ->setParameter('applicationId', $applicationId)
            ->orderBy('pa.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}