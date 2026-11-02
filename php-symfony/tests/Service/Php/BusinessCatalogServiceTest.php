<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\BusinessCatalog;
use App\Repository\Php\BusinessCatalogRepository;
use App\Service\Php\BusinessCatalogService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCatalogServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BusinessCatalogRepository&MockObject $repository;
    private BusinessCatalogService $service;

    private string $catalogId;
    private BusinessCatalog $catalog;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(BusinessCatalogRepository::class);
        $this->service = new BusinessCatalogService($this->em, $this->repository);

        $this->catalogId = bin2hex(random_bytes(16));
        $this->catalog = new BusinessCatalog(
            $this->catalogId,
            'business-1',
            'My Catalog',
            'RETAIL'
        );
    }

    public function testCreateBusinessCatalogSuccess(): void
    {
        $businessId = 'biz-1';
        $name = 'Catalog A';
        $category = 'ELECTRONICS';
        $description = 'Best items';
        $items = ['item1', 'item2'];
        $metadata = ['tag' => 'sale'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (BusinessCatalog $cat) use (&$captured, $businessId, $name, $category, $description, $items, $metadata) {
                $captured = $cat;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBusinessCatalog($businessId, $name, $category, $description, $items, $metadata);
        $this->assertInstanceOf(BusinessCatalog::class, $result);
        $this->assertSame($businessId, $result->getBusinessId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($category, $result->getCategory());
        $this->assertSame($description, $result->getDescription());
        $this->assertSame($items, $result->getItems());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateBusinessCatalogValidationEmptyBusinessId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business ID is required');
        $this->service->createBusinessCatalog('', 'Name', 'CAT');
    }

    public function testCreateBusinessCatalogValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createBusinessCatalog('biz', '   ', 'CAT');
    }

    public function testCreateBusinessCatalogValidationEmptyCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category is required');
        $this->service->createBusinessCatalog('biz', 'Name', '');
    }

    public function testGetBusinessCatalogSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);

        $result = $this->service->getBusinessCatalog($this->catalogId);
        $this->assertSame($this->catalog, $result);
    }

    public function testGetBusinessCatalogNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('BusinessCatalog not found');
        $this->service->getBusinessCatalog($this->catalogId);
    }

    public function testUpdateBusinessCatalogSuccess(): void
    {
        $newBusinessId = 'biz-2';
        $newName = 'Updated Catalog';
        $newCategory = 'BOOKS';
        $newDescription = 'New desc';
        $newItems = ['newitem'];
        $newMetadata = ['v' => 2];
        $newStatus = 'ACTIVE';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'businessId' => $newBusinessId,
            'name' => $newName,
            'category' => $newCategory,
            'description' => $newDescription,
            'items' => $newItems,
            'metadata' => $newMetadata,
            'status' => $newStatus,
        ];
        $result = $this->service->updateBusinessCatalog($this->catalogId, $data);
        $this->assertSame($this->catalog, $result);
        $this->assertSame($newBusinessId, $result->getBusinessId());
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newCategory, $result->getCategory());
        $this->assertSame($newDescription, $result->getDescription());
        $this->assertSame($newItems, $result->getItems());
        $this->assertSame($newMetadata, $result->getMetadata());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateBusinessCatalogValidationEmptyBusinessId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business ID cannot be empty');
        $this->service->updateBusinessCatalog($this->catalogId, ['businessId' => '']);
    }

    public function testUpdateBusinessCatalogValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateBusinessCatalog($this->catalogId, ['name' => '   ']);
    }

    public function testUpdateBusinessCatalogValidationEmptyCategory(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category cannot be empty');
        $this->service->updateBusinessCatalog($this->catalogId, ['category' => '']);
    }

    public function testUpdateBusinessCatalogNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateBusinessCatalog($this->catalogId, []);
    }

    public function testDeleteBusinessCatalogSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn($this->catalog);
        $this->em->expects($this->once())->method('remove')->with($this->catalog);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteBusinessCatalog($this->catalogId);
    }

    public function testDeleteBusinessCatalogNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->catalogId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteBusinessCatalog($this->catalogId);
    }

    public function testFindByBusinessId(): void
    {
        $businessId = 'biz-1';
        $results = [$this->catalog];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['businessId' => $businessId])
            ->willReturn($results);

        $result = $this->service->findByBusinessId($businessId);
        $this->assertSame($results, $result);
    }

    public function testFindByCategory(): void
    {
        $category = 'ELECTRONICS';
        $results = [$this->catalog];
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
        $results = [$this->catalog];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testAddItemToCatalog(): void
    {
        $catalogId = $this->catalogId;
        $newItem = ['name' => 'New Product', 'price' => 100];
        // Initially catalog has no items
        $this->catalog->setItems([]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($catalogId)
            ->willReturn($this->catalog);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->addItemToCatalog($catalogId, $newItem);
        $this->assertSame($this->catalog, $result);
        $this->assertContains($newItem, $result->getItems());
    }

    public function testRemoveItemFromCatalog(): void
    {
        $catalogId = $this->catalogId;
        $existingItems = [['name' => 'A'], ['name' => 'B']];
        $this->catalog->setItems($existingItems);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($catalogId)
            ->willReturn($this->catalog);
        $this->em->expects($this->once())->method('flush');

        $indexToRemove = 0;
        $result = $this->service->removeItemFromCatalog($catalogId, $indexToRemove);
        $this->assertSame($this->catalog, $result);
        $this->assertCount(1, $result->getItems());
        $this->assertSame(['name' => 'B'], $result->getItems()[0]);
    }

    public function testRemoveItemFromCatalogWhenIndexInvalidThrows(): void
    {
        $catalogId = $this->catalogId;
        $existingItems = [['name' => 'A']];
        $this->catalog->setItems($existingItems);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($catalogId)
            ->willReturn($this->catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item not found at index');
        $this->service->removeItemFromCatalog($catalogId, 5);
    }
}
