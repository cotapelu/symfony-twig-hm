<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\EcommerceProduct;
use App\Repository\Php\EcommerceProductRepository;
use App\Service\Php\EcommerceProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EcommerceProductServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EcommerceProductRepository&MockObject $repository;
    private EcommerceProductService $service;

    private string $productId;
    private EcommerceProduct $product;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EcommerceProductRepository::class);
        $this->service = new EcommerceProductService($this->em, $this->repository);

        $this->productId = bin2hex(random_bytes(16));
        $this->product = new EcommerceProduct(
            $this->productId,
            'business-1',
            'Product A',
            'Electronics',
            10000
        );
    }

    public function testCreateEcommerceProductSuccess(): void
    {
        $businessId = 'biz-1';
        $name = 'Laptop';
        $category = 'COMPUTERS';
        $priceCents = 150000;
        $description = 'High-end laptop';
        $stockQuantity = 50;
        $attributes = ['color' => 'silver', 'weight' => '2kg'];
        $metadata = ['warranty' => '2 years'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EcommerceProduct $p) use (&$captured, $businessId, $name, $category, $priceCents, $description, $stockQuantity, $attributes, $metadata) {
                $captured = $p;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createEcommerceProduct($businessId, $name, $category, $priceCents, $description, $stockQuantity, $attributes, $metadata);
        $this->assertInstanceOf(EcommerceProduct::class, $result);
        $this->assertSame($businessId, $result->getBusinessId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($category, $result->getCategory());
        $this->assertSame($priceCents, $result->getPriceCents());
        $this->assertSame($description, $result->getDescription());
        $this->assertSame($stockQuantity, $result->getStockQuantity());
        $this->assertSame($attributes, $result->getAttributes());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateEcommerceProductValidationEmptyBusinessId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business ID is required');
        $this->service->createEcommerceProduct('', 'Name', 'Cat', 100);
    }

    public function testCreateEcommerceProductValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createEcommerceProduct('biz', '   ', 'cat', 100);
    }

    public function testCreateEcommerceProductValidationEmptyCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category is required');
        $this->service->createEcommerceProduct('biz', 'name', '', 100);
    }

    public function testCreateEcommerceProductValidationPriceNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->createEcommerceProduct('biz', 'name', 'cat', 0);
    }

    public function testGetEcommerceProductSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $result = $this->service->getEcommerceProduct($this->productId);
        $this->assertSame($this->product, $result);
    }

    public function testGetEcommerceProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('EcommerceProduct not found');
        $this->service->getEcommerceProduct($this->productId);
    }

    public function testUpdateEcommerceProductSuccess(): void
    {
        $newBusinessId = 'biz-2';
        $newName = 'Updated Product';
        $newCategory = 'ACCESSORIES';
        $newPrice = 2000;
        $newDescription = 'New desc';
        $newStock = 100;
        $newAttributes = ['size' => 'M'];
        $newMetadata = ['discount' => '10%'];
        $newStatus = 'DISCONTINUED';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'businessId' => $newBusinessId,
            'name' => $newName,
            'category' => $newCategory,
            'priceCents' => $newPrice,
            'description' => $newDescription,
            'stockQuantity' => $newStock,
            'attributes' => $newAttributes,
            'metadata' => $newMetadata,
            'status' => $newStatus,
        ];
        $result = $this->service->updateEcommerceProduct($this->productId, $data);
        $this->assertSame($this->product, $result);
        $this->assertSame($newBusinessId, $result->getBusinessId());
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newCategory, $result->getCategory());
        $this->assertSame($newPrice, $result->getPriceCents());
        $this->assertSame($newDescription, $result->getDescription());
        $this->assertSame($newStock, $result->getStockQuantity());
        $this->assertSame($newAttributes, $result->getAttributes());
        $this->assertSame($newMetadata, $result->getMetadata());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateEcommerceProductValidationEmptyBusinessId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business ID cannot be empty');
        $this->service->updateEcommerceProduct($this->productId, ['businessId' => '']);
    }

    public function testUpdateEcommerceProductValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateEcommerceProduct($this->productId, ['name' => '   ']);
    }

    public function testUpdateEcommerceProductValidationEmptyCategory(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category cannot be empty');
        $this->service->updateEcommerceProduct($this->productId, ['category' => '']);
    }

    public function testUpdateEcommerceProductValidationPriceNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->service->updateEcommerceProduct($this->productId, ['priceCents' => -1]);
    }

    public function testUpdateEcommerceProductValidationStockNegative(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative');
        $this->service->updateEcommerceProduct($this->productId, ['stockQuantity' => -5]);
    }

    public function testUpdateEcommerceProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateEcommerceProduct($this->productId, []);
    }

    public function testDeleteEcommerceProductSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn($this->product);
        $this->em->expects($this->once())->method('remove')->with($this->product);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteEcommerceProduct($this->productId);
    }

    public function testDeleteEcommerceProductNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->productId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteEcommerceProduct($this->productId);
    }

    public function testFindByBusinessId(): void
    {
        $businessId = 'biz-123';
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['businessId' => $businessId])
            ->willReturn($results);

        $result = $this->service->findByBusinessId($businessId);
        $this->assertSame($results, $result);
    }

    public function testFindByCategory(): void
    {
        $category = 'Electronics';
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn($results);

        $result = $this->service->findByCategory($category);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $results = [$this->product];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
