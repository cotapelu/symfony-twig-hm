<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\DeliveryProduct;
use App\Repository\Php\DeliveryProductRepository;
use App\Service\Php\DeliveryProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryProductServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DeliveryProductRepository&MockObject $repository;
    private DeliveryProductService $service;

    private string $productId;
    private DeliveryProduct $product;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryProductRepository::class);
        $this->service = new DeliveryProductService($this->em, $this->repository);

        $this->productId = bin2hex(random_bytes(16));
        $this->product = new DeliveryProduct(
            $this->productId,
            'store-1',
            'Food',
            'Pizza',
            999,
            10
        );
    }

    public function testCreateDeliveryProductSuccess(): void
    {
        $storeId = 'store-1';
        $category = 'Beverage';
        $name = 'Coke';
        $priceCents = 150;
        $stock = 50;
        $description = 'Soft drink';
        $isActive = true;
        $metadata = ['brand' => 'Coca-Cola'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DeliveryProduct $p) use (&$captured, $storeId, $category, $name, $priceCents, $stock, $description, $isActive, $metadata) {
                $captured = $p;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryProduct($storeId, $category, $name, $priceCents, $stock, $description, $isActive, $metadata);
        $this->assertInstanceOf(DeliveryProduct::class, $result);
        $this->assertSame($storeId, $result->getStoreId());
        $this->assertSame($category, $result->getCategory());
        $this->assertSame($name, $result->getName());
        $this->assertSame($priceCents, $result->getPriceCents());
        $this->assertSame($stock, $result->getStock());
        $this->assertSame($description, $result->getDescription());
        $this->assertSame($isActive, $result->isActive());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateDeliveryProductValidationEmptyStoreId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Store ID is required');
        $this->service->createDeliveryProduct('', 'cat', 'name', 100, 10);
    }

    public function testCreateDeliveryProductValidationEmptyCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category is required');
        $this->service->createDeliveryProduct('store', '', 'name', 100, 10);
    }

    public function testCreateDeliveryProductValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createDeliveryProduct('store', 'cat', '   ', 100, 10);
    }

    public function testCreateDeliveryProductValidationPriceNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->createDeliveryProduct('store', 'cat', 'name', 0, 10);
    }

    public function testCreateDeliveryProductValidationStockNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock cannot be negative');
        $this->service->createDeliveryProduct('store', 'cat', 'name', 100, -1);
    }

    public function testGetDeliveryProductSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $result = $this->service->getDeliveryProduct($this->productId);
        $this->assertSame($this->product, $result);
    }

    public function testGetDeliveryProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeliveryProduct not found');
        $this->service->getDeliveryProduct($this->productId);
    }

    public function testUpdateDeliveryProductSuccess(): void
    {
        $newStoreId = 'store-2';
        $newCategory = 'Dessert';
        $newName = 'Ice Cream';
        $newPrice = 200;
        $newStock = 20;
        $newDescription = 'Frozen';
        $newActive = false;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'storeId' => $newStoreId,
            'category' => $newCategory,
            'name' => $newName,
            'priceCents' => $newPrice,
            'stock' => $newStock,
            'description' => $newDescription,
            'isActive' => $newActive,
        ];
        $result = $this->service->updateDeliveryProduct($this->productId, $data);
        $this->assertSame($this->product, $result);
        $this->assertSame($newStoreId, $result->getStoreId());
        $this->assertSame($newCategory, $result->getCategory());
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newPrice, $result->getPriceCents());
        $this->assertSame($newStock, $result->getStock());
        $this->assertSame($newDescription, $result->getDescription());
        $this->assertSame($newActive, $result->isActive());
    }

    public function testUpdateDeliveryProductValidationEmptyStoreId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Store ID cannot be empty');
        $this->service->updateDeliveryProduct($this->productId, ['storeId' => '']);
    }

    public function testUpdateDeliveryProductValidationEmptyCategory(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category cannot be empty');
        $this->service->updateDeliveryProduct($this->productId, ['category' => '']);
    }

    public function testUpdateDeliveryProductValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateDeliveryProduct($this->productId, ['name' => '   ']);
    }

    public function testUpdateDeliveryProductValidationPriceNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->updateDeliveryProduct($this->productId, ['priceCents' => -1]);
    }

    public function testUpdateDeliveryProductValidationStockNegative(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock cannot be negative');
        $this->service->updateDeliveryProduct($this->productId, ['stock' => -5]);
    }

    public function testUpdateDeliveryProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryProduct($this->productId, []);
    }

    public function testDeleteDeliveryProductSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);
        $this->em->expects($this->once())->method('remove')->with($this->product);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryProduct($this->productId);
    }

    public function testDeleteDeliveryProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteDeliveryProduct($this->productId);
    }

    public function testFindByStoreId(): void
    {
        $storeId = 'store-1';
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['storeId' => $storeId])
            ->willReturn($results);

        $result = $this->service->findByStoreId($storeId);
        $this->assertSame($results, $result);
    }

    public function testFindByCategory(): void
    {
        $category = 'Food';
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn($results);

        $result = $this->service->findByCategory($category);
        $this->assertSame($results, $result);
    }

    public function testFindByIsActive(): void
    {
        $isActive = true;
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['isActive' => $isActive])
            ->willReturn($results);

        $result = $this->service->findByIsActive($isActive);
        $this->assertSame($results, $result);
    }
}
