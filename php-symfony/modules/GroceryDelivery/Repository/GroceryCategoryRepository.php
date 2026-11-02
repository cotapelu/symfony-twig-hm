<?php declare(strict_types=1);
namespace App\GroceryDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\GroceryCategory;

class GroceryCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroceryCategory::class);
    }

    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('gc')
            ->andWhere('gc.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('gc.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(GroceryCategory $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }
}
