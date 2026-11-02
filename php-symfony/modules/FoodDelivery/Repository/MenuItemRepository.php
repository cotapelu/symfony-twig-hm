<?php declare(strict_types=1);
namespace App\FoodDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\MenuItem;

class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    public function findByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.restaurant = :restaurantId')
            ->andWhere('m.isAvailable = :available')
            ->setParameter('restaurantId', $restaurantId)
            ->setParameter('available', true)
            ->orderBy('m.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $restaurantId, string $category): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.restaurant = :restaurantId')
            ->andWhere('m.category = :category')
            ->andWhere('m.isAvailable = :available')
            ->setParameter('restaurantId', $restaurantId)
            ->setParameter('category', $category)
            ->setParameter('available', true)
            ->getQuery()
            ->getResult();
    }

    public function save(MenuItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }
}
