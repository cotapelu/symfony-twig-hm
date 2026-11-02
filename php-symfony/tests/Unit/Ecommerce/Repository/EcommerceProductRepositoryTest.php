<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ecommerce\Repository;

use PHPUnit\Framework\TestCase;
use App\Ecommerce\Repository\EcommerceProductRepository;
use App\Ecommerce\Entity\EcommerceProduct;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Symfony\Contracts\Cache\CacheInterface;

class EcommerceProductRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;
    private EntityManagerInterface $em;
    private CacheInterface $cache;
    private ClassMetadata $classMeta;
    private EcommerceProductRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManager')->willReturn($this->em);
        $this->classMeta = $this->createMock(ClassMetadata::class);
        $this->classMeta->name = EcommerceProduct::class;
        $this->em->method('getClassMetadata')->willReturn($this->classMeta);
        $this->registry->method('getManagerForClass')
            ->with(EcommerceProduct::class)
            ->willReturn($this->em);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->repository = new EcommerceProductRepository($this->registry, $this->cache);
    }

    private function createQB(): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('update')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        return $qb;
    }

    // ==================== save ====================

    public function testSavePersistsFlushesAndInvalidatesCache(): void
    {
        $product = $this->createMock(EcommerceProduct::class);
        $product->method('getId')->willReturn('prod123');

        $this->em->expects($this->once())->method('persist')->with($product);
        $this->em->expects($this->once())->method('flush');
        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->with($this->anything());

        $this->repository->save($product);
    }

    // ==================== findById ====================

    public function testFindById(): void
    {
        $id = 'p1';
        $product = $this->createMock(EcommerceProduct::class);
        $partialRepo = $this->getMockBuilder(EcommerceProductRepository::class)
            ->setConstructorArgs([$this->registry, $this->cache])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $partialRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn($product);

        $result = $partialRepo->findById($id);
        $this->assertSame($product, $result);
    }

    // ==================== findBySeller ====================

    public function testFindBySellerBuildsQueryWithParameters(): void
    {
        $sellerId = 'seller_1';
        $limit = 10;
        $offset = 20;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('where')->with('p.sellerId = :sellerId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('sellerId', $sellerId)->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('p.createdAt', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();

        $result = $this->repository->findBySeller($sellerId, $limit, $offset);
        $this->assertSame([], $result);
    }

    // ==================== findByCategory ====================

    public function testFindByCategoryUsesCacheWhenOffsetZero(): void
    {
        $category = 'electronics';
        $limit = 20;
        $cachedProducts = [$this->createMock(EcommerceProduct::class)];
        $cacheKey = 'ecommerce_products_category_electronics_20';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback(fn($cb) => is_callable($cb)), 300)
            -> willReturn($cachedProducts);
        $this->em->expects($this->never())->method('createQueryBuilder');
        $result = $this->repository->findByCategory($category, $limit, 0);
        $this->assertSame($cachedProducts, $result);
    }

    public function testFindByCategoryWithoutCacheExecutesQuery(): void
    {
        $category = 'books';
        $limit = 10;
        $offset = 5;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$this->createMock(EcommerceProduct::class)]);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->cache->expects($this->never())->method('get');
        $qb->expects($this->once())->method('where')->with('p.category = :category')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('category', $category)->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();

        $result = $this->repository->findByCategory($category, $limit, $offset);
        $this->assertCount(1, $result);
    }

    // ==================== searchByName ====================

    public function testSearchByNameBuildsLikeQuery(): void
    {
        $search = 'phone';
        $limit = 5;
        $offset = 0;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('where')->with('p.name LIKE :query')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('query', '%phone%')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();

        $result = $this->repository->searchByName($search, $limit, $offset);
        $this->assertSame([], $result);
    }

    // ==================== searchFullText ====================

    public function testSearchFullTextBuildsMatchQuery(): void
    {
        $queryStr = 'laptop';
        $limit = 10;
        $offset = 0;
        $qb = $this->createQB();
        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($queryMock);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('where')
            ->with('MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE)')
            ->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('query', 'laptop')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();

        $result = $this->repository->searchFullText($queryStr, $limit, $offset);
        $this->assertSame([], $result);
    }

    // ==================== findActive ====================

    public function testFindActiveUsesCacheWhenOffsetZero(): void
    {
        $limit = 20;
        $cached = [$this->createMock(EcommerceProduct::class)];
        $cacheKey = 'ecommerce_products_active_20';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback(fn($cb) => is_callable($cb)), 300)
            ->willReturn($cached);
        $this->em->expects($this->never())->method('createQueryBuilder');
        $result = $this->repository->findActive($limit, 0);
        $this->assertSame($cached, $result);
    }

    public function testFindActiveWithoutCacheExecutesFindBy(): void
    {
        $limit = 5;
        $offset = 1;
        $products = [$this->createMock(EcommerceProduct::class)];
        // Partial mock to avoid complex QueryBuilder setup
        $repo = $this->getMockBuilder(EcommerceProductRepository::class)
            ->setConstructorArgs([$this->registry, null])
            ->onlyMethods(['findBy'])
            ->getMock();
        $repo->expects($this->once())
            ->method('findBy')
            ->with(
                ['isActive' => true],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            )
            ->willReturn($products);

        $result = $repo->findActive($limit, $offset);
        $this->assertSame($products, $result);
    }

    // ==================== countActive ====================

    public function testCountActiveUsesCacheWhenAvailable(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('ecommerce_products_count_active', $this->callback(fn($cb) => is_callable($cb)), 300)
            ->willReturn(42);
        $this->em->expects($this->never())->method('createQueryBuilder');
        $result = $this->repository->countActive();
        $this->assertSame(42, $result);
    }

    public function testCountActiveWithoutCacheExecutesCountQuery(): void
    {
        // Create repository without cache to test non-cache path
        $repo = new EcommerceProductRepository($this->registry, null);
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('123');
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('where')->with('p.isActive = :active')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('active', true)->willReturnSelf();

        $result = $repo->countActive();
        $this->assertSame(123, $result);
    }

    // ==================== updateStock ====================

    public function testUpdateStockExecutesUpdateAndInvalidatesCache(): void
    {
        $productId = 'p1';
        $newStock = 100;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(1);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->cache->expects($this->exactly(2))->method('delete')->with($this->anything());

        $qb->expects($this->once())->method('update')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('set')->willReturnSelf();
        $qb->expects($this->once())->method('where')
            ->with('p.id = :id')
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $this->repository->updateStock($productId, $newStock);
    }

    // ==================== decreaseStock ====================

    public function testDecreaseStockSuccessDecrementsAndInvalidatesCache(): void
    {
        $productId = 'p2';
        $quantity = 5;
        $affected = 1;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn($affected);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->cache->expects($this->exactly(2))->method('delete')->with($this->anything());

        $qb->expects($this->once())->method('update')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('set')->willReturnSelf();
        $qb->expects($this->once())->method('where')
            ->with('p.id = :id')
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $result = $this->repository->decreaseStock($productId, $quantity);
        $this->assertSame($affected, $result);
    }

    public function testDecreaseStockInsufficientStockReturnsZeroAndNoInvalidation(): void
    {
        $productId = 'p3';
        $quantity = 10;
        $affected = 0;
        $qb = $this->createQB();
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(0);
        $qb->method('getQuery')->willReturn($query);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->cache->expects($this->never())->method('delete');

        $qb->expects($this->once())->method('update')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('set')->willReturnSelf();
        $qb->expects($this->once())->method('where')
            ->with('p.id = :id')
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())->method('setParameter')->with($this->anything(), $this->anything())->willReturnSelf();

        $result = $this->repository->decreaseStock($productId, $quantity);
        $this->assertSame(0, $result);
    }
}