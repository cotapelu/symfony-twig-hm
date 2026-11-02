<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\DeliveryProductRepository;
use App\Entity\Php\DeliveryProduct;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryProductRepositoryTest extends TestCase
{
    private DeliveryProductRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(DeliveryProductRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $product = $this->createMock(DeliveryProduct::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($product);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($product);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $product = $this->createMock(DeliveryProduct::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($product);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($product);
    }

    public function testFindById(): void
    {
        $id = 'prod-123';
        $expected = $this->createMock(DeliveryProduct::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByStoreId(): void
    {
        $storeId = 'store-123';
        $expected = [$this->createMock(DeliveryProduct::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['storeId' => $storeId])
            ->willReturn($expected);

        $result = $this->repo->findByStoreId($storeId);
        $this->assertSame($expected, $result);
    }

    public function testFindByCategory(): void
    {
        $category = 'Food';
        $expected = [$this->createMock(DeliveryProduct::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn($expected);

        $result = $this->repo->findByCategory($category);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'OUT_OF_STOCK';
        $expected = [$this->createMock(DeliveryProduct::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveProducts(): void
    {
        $expected = [$this->createMock(DeliveryProduct::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveProducts();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveProducts(): void
    {
        $expected = [$this->createMock(DeliveryProduct::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveProducts();
        $this->assertSame($expected, $result);
    }

    public function testCountByStoreId(): void
    {
        $storeId = 'store-123';
        $count = 15;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['storeId' => $storeId])
            ->willReturn($count);

        $result = $this->repo->countByStoreId($storeId);
        $this->assertSame($count, $result);
    }

    public function testCountByCategory(): void
    {
        $category = 'Electronics';
        $count = 8;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['category' => $category])
            ->willReturn($count);

        $result = $this->repo->countByCategory($category);
        $this->assertSame($count, $result);
    }
}
