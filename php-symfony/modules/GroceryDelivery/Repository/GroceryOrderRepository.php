<?php declare(strict_types=1);
namespace App\GroceryDelivery\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\GroceryOrder;

class GroceryOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroceryOrder::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('go')
            ->andWhere('go.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('go.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('go')
            ->andWhere('go.status = :status')
            ->setParameter('status', $status)
            ->orderBy('go.orderedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOrders(): array
    {
        return $this->createQueryBuilder('go')
            ->andWhere('go.status IN (:statuses)')
            ->setParameter('statuses', [
                GroceryOrder::STATUS_PENDING,
                GroceryOrder::STATUS_CONFIRMED,
                GroceryOrder::STATUS_PREPARING,
                GroceryOrder::STATUS_READY,
                GroceryOrder::STATUS_PICKED_UP
            ])
            ->orderBy('go.orderedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(GroceryOrder $order): void
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $this->createQueryBuilder('go')
            ->update(GroceryOrder::class, 'go')
            ->set('go.status', ':status')
            ->where('go.id = :id')
            ->setParameter('status', $status)
            ->setParameter('id', $orderId)
            ->getQuery()
            ->execute();

        if ($status === GroceryOrder::STATUS_DELIVERED) {
            $this->createQueryBuilder('go')
                ->update(GroceryOrder::class, 'go')
                ->set('go.deliveredAt', ':deliveredAt')
                ->where('go.id = :id')
                ->setParameter('deliveredAt', new \DateTime())
                ->setParameter('id', $orderId)
                ->getQuery()
                ->execute();
        }
    }
}
