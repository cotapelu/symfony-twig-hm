<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\EcommerceProduct;
use App\Service\Php\EcommerceProductService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EcommerceProductServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private EcommerceProductService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new EcommerceProductService($this->em, $this->logger);
    }

    public function testCreateProduct(): void
    {
        $sellerId = 'seller_123';
        $name = 'iPhone 15';
        $description = 'Latest iPhone';
        $price = 999.99;
        $priceCents = 99999;
        $stock = 100;
        $category = 'Electronics';
        $imageUrl = 'iphone.jpg';
        $specs = ['color' => 'black', 'storage' => '256GB'];
        $metadata = ['brand' => 'Apple'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceProduct $p) use (
                $sellerId, $name, $description, $priceCents, $stock, $category, $imageUrl, $specs, $metadata
            ) {
                return $p->getSellerId() === $sellerId
                    && $p->getName() === $name
                    && $p->getDescription() === $description
                    && $p->getPriceCents() === $priceCents
                    && $p->getStock() === $stock
                    && $p->getCategory() === $category
                    && $p->getImageUrl() === $imageUrl
                    && $p->getSpecifications() === $specs
                    && $p->getMetadata() === $metadata
                    && $p->getStatus() === 'ACTIVE';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Ecommerce product created', $this->anything());

        $result = $this->service->createProduct($sellerId, $name, $description, $price, $stock, $category, $imageUrl, $specs, $metadata);
        $this->assertInstanceOf(EcommerceProduct::class, $result);
        $this->assertSame($priceCents, $result->getPriceCents());
        $this->assertSame('ACTIVE', $result->getStatus());
        $this->assertSame(0, $result->getViews());
    }

    public function testCreateProductMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createProduct('seller', 'Name', 'Desc', 19.99, 10, 'Cat');
        $this->assertNull($result->getImageUrl());
        $this->assertNull($result->getSpecifications());
        $this->assertNull($result->getMetadata());
    }

    public function testGetProduct(): void
    {
        $id = 'product_123';
        $product = new EcommerceProduct($id, 'seller', 'Name', 'Desc', 1000, 10, 'Cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getProduct($id);
        $this->assertSame($product, $result);
    }

    public function testGetProductsBySeller(): void
    {
        $sellerId = 'seller_123';
        $products = [
            new EcommerceProduct('id1', $sellerId, 'Product1', 'D', 1000, 10, 'Cat'),
            new EcommerceProduct('id2', $sellerId, 'Product2', 'D', 2000, 20, 'Cat'),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['sellerId' => $sellerId])
            ->willReturn($products);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProductsBySeller($sellerId);
        $this->assertCount(2, $result);
    }

    public function testGetActiveProducts(): void
    {
        $products = [
            new EcommerceProduct('id1', 'seller', 'P1', 'D', 1000, 10, 'Cat'),
        ];
        $products[0]->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($products);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getActiveProducts();
        $this->assertCount(1, $result);
    }

    public function testGetProductsByCategory(): void
    {
        $category = 'Electronics';
        $products = [
            new EcommerceProduct('id1', 'seller', 'P1', 'D', 1000, 10, $category),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category, 'status' => 'ACTIVE'])
            ->willReturn($products);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProductsByCategory($category);
        $this->assertCount(1, $result);
    }

    public function testSearchByName(): void
    {
        $query = 'phone';
        $products = [new EcommerceProduct('id', 'seller', 'iPhone', 'D', 1000, 10, 'Cat')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.name LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $queryObj = $this->createMock(\Doctrine\ORM\Query::class);
        $queryObj->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryObj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->searchByName($query);
        $this->assertCount(1, $result);
    }

    public function testUpdateProduct(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Old Name', 'Old Desc', 1000, 10, 'Cat');
        $newName = 'New Name';
        $newPrice = 1999.99;

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateProduct($productId, $newName, null, $newPrice);
        $this->assertSame($product, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame(199999, $result->getPriceCents());
    }

    public function testUpdateProductNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateProduct('nonexistent', 'Name');
        $this->assertNull($result);
    }

    public function testSetProductStatus(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 1000, 10, 'Cat');
        $product->setStatus('ACTIVE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->setProductStatus($productId, 'INACTIVE');
        $this->assertSame($product, $result);
        $this->assertSame('INACTIVE', $result->getStatus());
    }

    public function testAdjustStockIncrease(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 1000, 10, 'Cat');
        $delta = 5;

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->adjustStock($productId, $delta);
        $this->assertSame($product, $result);
        $this->assertSame(15, $result->getStock());
    }

    public function testAdjustStockNegative(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 1000, 20, 'Cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->adjustStock($productId, -5);
        $this->assertSame($product, $result);
        $this->assertSame(15, $result->getStock());
    }

    public function testApplyDiscount(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 2000, 10, 'Cat');
        $discountPrice = 1500.00;
        $start = new \DateTime('now');
        $end = new \DateTime('+1 week');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->applyDiscount($productId, $discountPrice, $start, $end);
        $this->assertSame($product, $result);
        $this->assertSame(150000, $result->getDiscountPriceCents());
        $this->assertSame($start, $result->getDiscountStartAt());
        $this->assertSame($end, $result->getDiscountEndAt());
        $this->assertSame('SALE', $result->getStatus());
    }

    public function testRemoveDiscount(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 2000, 10, 'Cat');
        $product->setDiscountPriceCents(1500);
        $product->setDiscountStartAt(new \DateTime());
        $product->setDiscountEndAt(new \DateTime('+1 week'));
        $product->setStatus('SALE');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->removeDiscount($productId);
        $this->assertSame($product, $result);
        $this->assertNull($result->getDiscountPriceCents());
        $this->assertNull($result->getDiscountStartAt());
        $this->assertNull($result->getDiscountEndAt());
        $this->assertSame('ACTIVE', $result->getStatus());
    }

    public function testGetLowStockProducts(): void
    {
        $threshold = 5;
        $products = [
            new EcommerceProduct('id1', 'seller', 'P1', 'D', 1000, 3, 'Cat'),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.stock <= :threshold')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getLowStockProducts($threshold);
        $this->assertCount(1, $result);
    }

    public function testGetDiscountedProducts(): void
    {
        $now = new \DateTime();
        $products = [
            new EcommerceProduct('id1', 'seller', 'P1', 'D', 2000, 10, 'Cat', null, 1500, $now, new \DateTime('+1 day')),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.discountPriceCents IS NOT NULL')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.discountStartAt <= :now')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.discountEndAt >= :now')
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getDiscountedProducts($now);
        $this->assertCount(1, $result);
    }

    public function testGetProductsByPriceRange(): void
    {
        $min = 10.00;
        $max = 100.00;
        $products = [new EcommerceProduct('id', 'seller', 'P', 'D', 1000, 10, 'Cat')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.priceCents >= :min')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.priceCents <= :max')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getProductsByPriceRange($min, $max);
        $this->assertCount(1, $result);
    }

    public function testGetFeaturedProducts(): void
    {
        $products = [new EcommerceProduct('id', 'seller', 'P', 'D', 1000, 10, 'Cat')];
        $products[0]->setFeatured(true);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['featured' => true])
            ->willReturn($products);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getFeaturedProducts();
        $this->assertCount(1, $result);
    }

    public function testSetFeatured(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 1000, 10, 'Cat');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->setFeatured($productId, true);
        $this->assertSame($product, $result);
        $this->assertTrue($result->isFeatured());
    }

    public function testGetProductsByTags(): void
    {
        $tag = 'electronics';
        $products = [new EcommerceProduct('id', 'seller', 'P', 'D', 1000, 10, 'Cat')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with("JSON_CONTAINS(p.metadata, '\"electronics\"', '$')")
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getProductsByTags($tag);
        $this->assertCount(1, $result);
    }

    public function testIncrementViews(): void
    {
        $productId = 'product_123';
        $product = new EcommerceProduct($productId, 'seller', 'Name', 'D', 1000, 10, 'Cat');
        $product->setViews(10);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->incrementViews($productId);
        $this->assertSame($product, $result);
        $this->assertSame(11, $result->getViews());
    }

    public function testGetMostViewedProducts(): void
    {
        $limit = 5;
        $products = [new EcommerceProduct('id', 'seller', 'P', 'D', 1000, 10, 'Cat')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.views', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getMostViewedProducts($limit);
        $this->assertCount(1, $result);
    }

    public function testGetNewArrivals(): void
    {
        $days = 7;
        $products = [new EcommerceProduct('id', 'seller', 'P', 'D', 1000, 10, 'Cat')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.createdAt >= :since')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('since', $this->anything())
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getNewArrivals($days);
        $this->assertCount(1, $result);
    }

    public function testGetProductsByTagsEmpty(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->createQueryBuilderResult([]));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EcommerceProduct::class)
            ->willReturn($repo);

        $result = $this->service->getProductsByTags('nonexistent');
        $this->assertEmpty($result);
    }

    private function createQueryBuilderResult(array $result): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn($result);
        $qb->method('getQuery')->willReturn($query);
        return $qb;
    }
}
