<?php declare(strict_types=1);
namespace App\GroceryDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\GroceryProduct;

class GroceryProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroceryProduct::class);
    }

    public function findByStore(int $storeId): array
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.store = :storeId')
            ->andWhere('gp.isAvailable = :available')
            ->setParameter('storeId', $storeId)
            ->setParameter('available', true)
            ->orderBy('gp.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.category = :category')
            ->andWhere('gp.isAvailable = :available')
            ->setParameter('category', $category)
            ->setParameter('available', true)
            ->orderBy('gp.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeatured(int $limit = 10): array
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.isFeatured = :featured')
            ->andWhere('gp.isAvailable = :available')
            ->setParameter('featured', true)
            ->setParameter('available', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.name LIKE :search')
            ->andWhere('gp.isAvailable = :available')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->setParameter('available', true)
            ->orderBy('gp.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(GroceryProduct $product): void
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }

    public function decrementStock(int $productId, int $quantity): void
    {
        $this->createQueryBuilder('gp')
            ->update(GroceryProduct::class, 'gp')
            ->set('gp.stock', 'gp.stock - :quantity')
            ->setParameter('quantity', $quantity)
            ->where('gp.id = :id')
            ->setParameter('id', $productId)
            ->getQuery()
            ->execute();
    }
}
