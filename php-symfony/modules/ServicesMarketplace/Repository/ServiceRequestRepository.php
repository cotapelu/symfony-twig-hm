<?php

declare(strict_types=1);

namespace App\ServicesMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ServicesMarketplace\Entity\ServiceRequest;

class ServiceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceRequest::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.category = :category')
            ->setParameter('category', $category)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOpenRequests(): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.status = :status')
            ->setParameter('status', ServiceRequest::STATUS_OPEN)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ServiceRequest $request): void
    {
        $this->getEntityManager()->persist($request);
        $this->getEntityManager()->flush();
    }

    public function delete(ServiceRequest $request): void
    {
        $this->getEntityManager()->remove($request);
        $this->getEntityManager()->flush();
    }
}
