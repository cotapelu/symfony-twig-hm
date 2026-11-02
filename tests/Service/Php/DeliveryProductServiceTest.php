<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\DeliveryProduct;
use App\Service\Php\DeliveryProductService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeliveryProductServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private DeliveryProductService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new DeliveryProductService($this->em, $this->logger);
    }

    public function testCreateProductSuccess(): void
    {
        $storeId = 'store_123';
        $name = 'Test Product';
        $description = 'A test product';
        $price = 19.99;
        $stock = 100;
        $category = 'food';
        $imageUrl = 'http://example.com/image.jpg';
        $isAvailable = true;
        $options = ['size' => 'M'];
        $metadata = ['brand' => 'TestBrand'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryProduct::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryProduct $p) use (
                $storeId, $name, $description, $price, $stock, $category, $imageUrl, $isAvailable, $options, $metadata
            ) {
                return $p->getStoreId() === $storeId
                    && $p->getName() === $name
                    && $p->getDescription() === $description
                    && $p->getPriceCents() === (int)($price * 100)
                    && $p->getStockQuantity() === $stock
                    && $p->getCategory() === $category
                    && $p->getImageUrl() === $imageUrl
                    && $p->getIsAvailable() === $isAvailable
                    && $p->getOptions() === $options
                    && $p->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Delivery product created', $this->anything());

        $result = $this->service->createProduct(
            $storeId, $name, $description, $price, $stock, $category,
            $imageUrl, $isAvailable, $options, $metadata
        );
        $this->assertInstanceOf(DeliveryProduct::class, $result);
        $this->assertSame($storeId, $result->getStoreId());
        $this->assertSame($name, $result->getName());
        $this->assertSame((int)($price * 100), $result->getPriceCents());
    }

    public function testGetProductSuccess(): void
    {
        $id = 'product_123';
        $product = new DeliveryProduct($id, 'store', 'Product', 'Desc', 1999, 10, 'food');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryProduct::class)
            ->willReturn($repo);

        $result = $this->service->getProduct($id);
        $this->assertSame($product, $result);
    }

    public function testGetProductsByStore(): void
    {
        $storeId = 'store_123';
        $p1 = new DeliveryProduct('id1', $storeId, 'P1', 'D1', 1000, 5, 'food');
        $p2 = new DeliveryProduct('id2', $storeId, 'P2', 'D2', 2000, 10, 'drink');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['storeId' => $storeId])
            ->willReturn([$p1, $p2]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProductsByStore($storeId);
        $this->assertCount(2, $result);
    }

    public function testGetAvailableProducts(): void
    {
        $p = new DeliveryProduct('id', 'store', 'Product', 'Desc', 1000, 5, 'food');
        $p->setIsAvailable(true);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['isAvailable' => true])
            ->willReturn([$p]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getAvailableProducts();
        $this->assertCount(1, $result);
    }

    public function testGetProductsByCategory(): void
    {
        $category = 'beverage';
        $p = new DeliveryProduct('id', 'store', 'Product', 'Desc', 1000, 5, $category);
        $p->setIsAvailable(true);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category, 'isAvailable' => true])
            ->willReturn([$p]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getProductsByCategory($category);
        $this->assertCount(1, $result);
    }

    public function testSearchProducts(): void
    {
        $query = 'test';
        $p1 = new DeliveryProduct('id1', 'store', 'Test Product', 'D1', 1000, 5, 'food');
        $p2 = new DeliveryProduct('id2', 'store', 'Another Test', 'D2', 2000, 10, 'drink');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('p.name LIKE :query OR p.description LIKE :query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('query', '%' . $query . '%')
            ->willReturnSelf();
        $query_obj = $this->createMock(\Doctrine\ORM\Query::class);
        $query_obj->expects($this->once())
            ->method('getResult')
            ->willReturn([$p1, $p2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query_obj);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DeliveryProduct::class)
            ->willReturn($repo);

        $result = $this->service->searchProducts($query);
        $this->assertCount(2, $result);
    }

    public function testUpdateProduct(): void
    {
        $productId = 'product_123';
        $product = new DeliveryProduct($productId, 'store', 'Old Name', 'Desc', 1000, 5, 'food');
        $newName = 'New Name';
        $newPrice = 29.99;
        $newStock = 50;

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

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Delivery product updated', ['product_id' => $productId]);

        $result = $this->service->updateProduct($productId, $newName, $newPrice, $newStock);
        $this->assertSame($product, $result);
        $this->assertSame($newName, $result->getName());
        $this->assertSame((int)($newPrice * 100), $result->getPriceCents());
        $this->assertSame($newStock, $result->getStockQuantity());
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

        $result = $this->service->updateProduct('nonexistent', 'name', 10.0, 5);
        $this->assertNull($result);
    }

    public function testDeleteProductSuccess(): void
    {
        $productId = 'product_123';
        $product = new DeliveryProduct($productId, 'store', 'Product', 'Desc', 1000, 5, 'food');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($product);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteProduct($productId);
        $this->assertTrue($result);
    }

    public function testDeleteProductNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteProduct('nonexistent');
        $this->assertFalse($result);
    }
}
