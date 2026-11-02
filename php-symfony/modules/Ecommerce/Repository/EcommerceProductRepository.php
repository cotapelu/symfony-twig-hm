<?php

declare(strict_types=1);

namespace App\Ecommerce\Repository;

use App\Ecommerce\Entity\EcommerceProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @extends ServiceEntityRepository<EcommerceProduct>
 */
class EcommerceProductRepository extends ServiceEntityRepository
{
    private const CACHE_TTL = 300;
    private ?CacheInterface $cache = null;

    public function __construct(ManagerRegistry $registry, ?CacheInterface $cache = null)
    {
        parent::__construct($registry, EcommerceProduct::class);
        $this->cache = $cache;
    }

    public function save(EcommerceProduct $product): void
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
        $this->invalidateProductCache($product->getId());
    }

    public function findById(string $id): ?EcommerceProduct
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findBySeller(string $sellerId, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.sellerId = :sellerId')
            ->setParameter('sellerId', $sellerId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        if ($this->cache !== null && $offset === 0) {
            $cacheKey = sprintf('ecommerce_products_category_%s_%d', $category, $limit);
            return $this->cache->get($cacheKey, function () use ($category, $limit, $offset) {
                return $this->createQueryBuilder('p')
                    ->where('p.category = :category')
                    ->setParameter('category', $category)
                    ->orderBy('p.createdAt', 'DESC')
                    ->setMaxResults($limit)
                    ->setFirstResult($offset)
                    ->getQuery()
                    ->getResult();
            }, self::CACHE_TTL);
        }

        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->setParameter('category', $category)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function searchFullText(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE)')
            ->setParameter('query', $query)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findActive(int $limit = 20, int $offset = 0): array
    {
        if ($this->cache !== null && $offset === 0) {
            $cacheKey = sprintf('ecommerce_products_active_%d', $limit);
            return $this->cache->get($cacheKey, function () use ($limit, $offset) {
                return $this->findBy(['isActive' => true], ['createdAt' => 'DESC'], $limit, $offset);
            }, self::CACHE_TTL);
        }

        return $this->findBy(['isActive' => true], ['createdAt' => 'DESC'], $limit, $offset);
    }

    public function countActive(): int
    {
        if ($this->cache !== null) {
            return $this->cache->get('ecommerce_products_count_active', function () {
                return (int)$this->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->where('p.isActive = :active')
                    ->setParameter('active', true)
                    ->getQuery()
                    ->getSingleScalarResult();
            }, self::CACHE_TTL);
        }

        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateStock(string $productId, int $stock): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.stock', ':stock')
            ->set('p.updatedAt', ':now')
            ->where('p.id = :id')
            ->setParameter('stock', $stock)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $productId)
            ->getQuery()
            ->execute();

        $this->invalidateProductCache($productId);
    }

    public function decreaseStock(string $productId, int $quantity): int
    {
        $affected = $this->createQueryBuilder('p')
            ->update()
            ->set('p.stock', 'p.stock - :quantity')
            ->set('p.updatedAt', ':now')
            ->where('p.id = :id')
            ->andWhere('p.stock >= :quantity')
            ->setParameter('quantity', $quantity)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $productId)
            ->getQuery()
            ->execute();

        if ($affected > 0) {
            $this->invalidateProductCache($productId);
        }

        return (int) $affected;
    }

    private function invalidateProductCache(string $productId): void
    {
        if ($this->cache === null) {
            return;
        }

        $this->cache->delete('ecommerce_products_active_20');
        $this->cache->delete('ecommerce_products_count_active');
    }
}
