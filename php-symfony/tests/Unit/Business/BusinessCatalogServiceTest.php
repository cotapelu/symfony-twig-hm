<?php

declare(strict_types=1);

namespace App\Tests\Unit\Business;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\BusinessCatalogService;
use App\Entity\Php\BusinessCatalog;
use App\Repository\Php\BusinessCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class BusinessCatalogServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private BusinessCatalogRepository $repository;
    private BusinessCatalogService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(BusinessCatalogRepository::class);
        $this->service = new BusinessCatalogService($this->em, $this->repository);
    }

    public function testCreateCatalog_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBusinessCatalog(
            'biz-123',
            'My Catalog',
            'electronics',
            'Desc here',
            [['name' => 'Item1']],
            ['meta' => 'value']
        );

        $this->assertInstanceOf(BusinessCatalog::class, $result);
        $this->assertEquals('biz-123', $result->getBusinessId());
        $this->assertEquals('My Catalog', $result->getName());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateCatalog_EmptyFields_ThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBusinessCatalog('', 'Name', 'cat');
    }

    public function testGetCatalog_Exists(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Catalog', 'cat');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($catalog);
        $result = $this->service->getBusinessCatalog('id-1');
        $this->assertSame($catalog, $result);
    }

    public function testGetCatalog_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getBusinessCatalog('missing');
    }

    public function testUpdateCatalog_Success(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Old', 'cat');
        $this->repository->expects($this->once())->method('find')->willReturn($catalog);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateBusinessCatalog('id-1', [
            'name' => 'New Name',
            'category' => 'new-cat',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals('new-cat', $result->getCategory());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateCatalog_InvalidBusinessId_Throws(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Name', 'cat');
        $this->repository->expects($this->once())->method('find')->willReturn($catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateBusinessCatalog('id-1', ['businessId' => '']);
    }

    public function testUpdateCatalog_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateBusinessCatalog('missing', ['name' => 'New']);
    }

    public function testDeleteCatalog_Success(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Name', 'cat');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($catalog);
        $this->em->expects($this->once())->method('remove')->with($catalog);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteBusinessCatalog('id-1');
    }

    public function testFindByBusinessId_ReturnsArray(): void
    {
        $catalogs = [new BusinessCatalog('id-1', 'biz-123', 'Name', 'cat')];
        $this->repository->expects($this->once())->method('findBy')->with(['businessId' => 'biz-123'])->willReturn($catalogs);
        $result = $this->service->findByBusinessId('biz-123');
        $this->assertCount(1, $result);
    }

    public function testFindByCategory_ReturnsArray(): void
    {
        $catalogs = [new BusinessCatalog('id-1', 'biz-1', 'Name', 'electronics')];
        $this->repository->expects($this->once())->method('findBy')->with(['category' => 'electronics'])->willReturn($catalogs);
        $result = $this->service->findByCategory('electronics');
        $this->assertCount(1, $result);
    }

    public function testAddItemToCatalog_Success(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Catalog', 'cat');
        $catalog->setItems([]);

        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($catalog);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->addItemToCatalog('id-1', ['name' => 'New Item']);

        $this->assertEquals([['name' => 'New Item']], $result->getItems());
    }

    public function testRemoveItemFromCatalog_Success(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Catalog', 'cat');
        $catalog->setItems([['name' => 'Item1'], ['name' => 'Item2']]);

        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($catalog);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->removeItemFromCatalog('id-1', 0);

        $this->assertEquals([['name' => 'Item2']], $result->getItems());
    }

    public function testRemoveItemFromCatalog_InvalidIndex_Throws(): void
    {
        $catalog = new BusinessCatalog('id-1', 'biz-1', 'Catalog', 'cat');
        $catalog->setItems([['name' => 'Item1']]);

        $this->repository->expects($this->once())->method('find')->willReturn($catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item not found at index');
        $this->service->removeItemFromCatalog('id-1', 5);
    }
}
