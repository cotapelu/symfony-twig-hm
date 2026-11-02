<?php declare(strict_types=1);
namespace App\GroceryDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\GroceryCartItem;

class GroceryCartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroceryCartItem::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('gci')
            ->andWhere('gci.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('gci.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndProduct(string $userId, int $productId): ?GroceryCartItem
    {
        return $this->createQueryBuilder('gci')
            ->andWhere('gci.userId = :userId')
            ->andWhere('gci.product = :productId')
            ->setParameter('userId', $userId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function clearUserCart(string $userId): void
    {
        $this->createQueryBuilder('gci')
            ->delete(GroceryCartItem::class, 'gci')
            ->andWhere('gci.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function save(GroceryCartItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    public function delete(GroceryCartItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}
