<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\CatalogItem;
use App\BusinessAccount\Repository\CatalogItemRepository;
use App\BusinessAccount\Service\CatalogService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CatalogServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CatalogItemRepository&MockObject $catalogRepo;
    private CatalogService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->catalogRepo = $this->createMock(CatalogItemRepository::class);
        $this->em->method('getRepository')->with(CatalogItem::class)->willReturn($this->catalogRepo);
        $this->service = new CatalogService($this->em);
    }

    public function testCreateCatalogItem(): void
    {
        $business = $this->createMock(Business::class);
        $data = [
            'name' => 'Product X',
            'price' => 99.99,
            'currency' => 'USD',
            'category' => 'Electronics',
            'description' => 'A great product',
            'imageUrl' => 'http://example.com/image.jpg',
            'stock' => 100,
        ];
        $this->em->expects($this->once())->method('persist')->with($this->callback(function($arg) use ($data, $business) {
            $this->assertInstanceOf(CatalogItem::class, $arg);
            $this->assertSame($business, $arg->getBusiness());
            $this->assertSame($data['name'], $arg->getName());
            $this->assertSame($data['price'], $arg->getPrice());
            $this->assertSame($data['currency'], $arg->getCurrency());
            $this->assertSame($data['category'], $arg->getCategory());
            $this->assertSame($data['description'], $arg->getDescription());
            $this->assertSame($data['imageUrl'], $arg->getImageUrl());
            $this->assertSame($data['stock'], $arg->getStock());
            $this->assertTrue($arg->isActive());
            return true;
        }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createCatalogItem($business, $data);
        $this->assertInstanceOf(CatalogItem::class, $result);
    }

    public function testUpdateCatalogItem(): void
    {
        $item = new CatalogItem();
        $updateData = [
            'name' => 'Updated Name',
            'price' => 199.99,
            'stock' => 50,
            'isActive' => false,
        ];
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->updateCatalogItem($item, $updateData);
        $this->assertSame($item, $result);
        $this->assertSame('Updated Name', $item->getName());
        $this->assertSame(199.99, $item->getPrice());
        $this->assertSame(50, $item->getStock());
        $this->assertFalse($item->isActive());
    }

    public function testDeleteCatalogItem(): void
    {
        $item = new CatalogItem();
        $this->em->expects($this->once())->method('remove')->with($item);
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->deleteCatalogItem($item);
        $this->assertSame($item, $result);
    }

    public function testGetBusinessCatalog(): void
    {
        $business = $this->createMock(Business::class);
        $business->method('getId')->willReturn(42);
        $items = [new CatalogItem(), new CatalogItem()];
        $this->catalogRepo->expects($this->once())->method('findByBusiness')->with(42)->willReturn($items);
        $result = $this->service->getBusinessCatalog($business);
        $this->assertSame($items, $result);
    }
}
