<?php declare(strict_types=1);
namespace App\ServicesMarketplace\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ServicesMarketplace\Entity\Service;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findByProvider(int $providerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.provider = :providerId')
            ->andWhere('s.isActive = :active')
            ->setParameter('providerId', $providerId)
            ->setParameter('active', true)
            ->orderBy('s.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :category')
            ->andWhere('s.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function save(Service $service): void
    {
        $this->getEntityManager()->persist($service);
        $this->getEntityManager()->flush();
    }

    public function incrementBookedCount(int $serviceId): void
    {
        $this->createQueryBuilder('s')
            ->update(Service::class, 's')
            ->set('s.bookedCount', 's.bookedCount + 1')
            ->where('s.id = :id')
            ->setParameter('id', $serviceId)
            ->getQuery()
            ->execute();
    }
}
