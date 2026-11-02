<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\DeliveryProductService;
use App\Entity\Php\DeliveryProduct;
use App\Repository\Php\DeliveryProductRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class DeliveryProductServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DeliveryProductRepository $repository;
    private DeliveryProductService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(DeliveryProductRepository::class);
        $this->service = new DeliveryProductService($this->em, $this->repository);
    }

    public function testCreateProduct_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createDeliveryProduct(
            'store-123',
            'FOOD',
            'Burger',
            250000,
            100,
            'Delicious burger',
            true,
            ['calories' => 500]
        );

        $this->assertInstanceOf(DeliveryProduct::class, $result);
        $this->assertEquals('store-123', $result->getStoreId());
        $this->assertEquals('FOOD', $result->getCategory());
        $this->assertEquals('Burger', $result->getName());
        $this->assertEquals(250000, $result->getPriceCents());
        $this->assertEquals(100, $result->getStock());
        $this->assertTrue($result->isActive());
    }

    public function testCreateProduct_InvalidPrice_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryProduct('store', 'cat', 'name', -100, 0);
    }

    public function testCreateProduct_InvalidStock_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createDeliveryProduct('store', 'cat', 'name', 1000, -5);
    }

    public function testGetProduct_Exists(): void
    {
        $product = new DeliveryProduct('id-1', 'store-1', 'CAT', 'Name', 1000, 50);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($product);
        $result = $this->service->getDeliveryProduct('id-1');
        $this->assertSame($product, $result);
    }

    public function testGetProduct_NotFound_Throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getDeliveryProduct('missing');
    }

    public function testUpdateProduct_Success(): void
    {
        $product = new DeliveryProduct('id-1', 'store-1', 'CAT', 'Name', 1000, 50);
        $this->repository->expects($this->once())->method('find')->willReturn($product);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateDeliveryProduct('id-1', [
            'name' => 'New Name',
            'priceCents' => 2000,
            'stock' => 30,
            'isActive' => false,
        ]);

        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals(2000, $result->getPriceCents());
        $this->assertEquals(30, $result->getStock());
        $this->assertFalse($result->isActive());
    }

    public function testUpdateProduct_InvalidPrice_Throws(): void
    {
        $product = new DeliveryProduct('id-1', 'store-1', 'CAT', 'Name', 1000, 50);
        $this->repository->expects($this->once())->method('find')->willReturn($product);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateDeliveryProduct('id-1', ['priceCents' => 0]);
    }

    public function testUpdateProduct_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateDeliveryProduct('missing', ['name' => 'New']);
    }

    public function testDeleteProduct_Success(): void
    {
        $product = new DeliveryProduct('id-1', 'store-1', 'CAT', 'Name', 1000, 50);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($product);
        $this->em->expects($this->once())->method('remove')->with($product);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteDeliveryProduct('id-1');
    }

    public function testFindByStoreId_ReturnsArray(): void
    {
        $products = [new DeliveryProduct('id-1', 'store-123', 'CAT', 'Name', 1000, 50)];
        $this->repository->expects($this->once())->method('findBy')->with(['storeId' => 'store-123'])->willReturn($products);
        $result = $this->service->findByStoreId('store-123');
        $this->assertCount(1, $result);
    }

    public function testFindByCategory_ReturnsArray(): void
    {
        $products = [new DeliveryProduct('id-1', 'store-1', 'FOOD', 'Name', 1000, 50)];
        $this->repository->expects($this->once())->method('findBy')->with(['category' => 'FOOD'])->willReturn($products);
        $result = $this->service->findByCategory('FOOD');
        $this->assertCount(1, $result);
    }

    public function testFindByIsActive_ReturnsArray(): void
    {
        $products = [new DeliveryProduct('id-1', 'store-1', 'CAT', 'Name', 1000, 50)];
        $this->repository->expects($this->once())->method('findBy')->with(['isActive' => true])->willReturn($products);
        $result = $this->service->findByIsActive(true);
        $this->assertCount(1, $result);
    }
}
