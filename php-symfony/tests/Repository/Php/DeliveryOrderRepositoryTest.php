<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\DeliveryOrderRepository;
use App\Entity\Php\DeliveryOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderRepositoryTest extends TestCase
{
    private DeliveryOrderRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(DeliveryOrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByStoreId(): void
    {
        $storeId = 'store-123';
        $expected = [$this->createMock(DeliveryOrder::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['storeId' => $storeId])
            ->willReturn($expected);

        $result = $this->repo->findByStoreId($storeId);
        $this->assertSame($expected, $result);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = 'cust-123';
        $expected = [$this->createMock(DeliveryOrder::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['customerId' => $customerId])
            ->willReturn($expected);

        $result = $this->repo->findByCustomerId($customerId);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'PENDING';
        $expected = [$this->createMock(DeliveryOrder::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindByDeliveryPersonId(): void
    {
        $deliveryPersonId = 'person-123';
        $expected = [$this->createMock(DeliveryOrder::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['deliveryPersonId' => $deliveryPersonId])
            ->willReturn($expected);

        $result = $this->repo->findByDeliveryPersonId($deliveryPersonId);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveOrders(): void
    {
        $expected = [$this->createMock(DeliveryOrder::class)];
        $statuses = ['PENDING', 'CONFIRMED', 'PREPARING', 'DELIVERING'];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $statuses])
            ->willReturn($expected);

        $result = $this->repo->findActiveOrders();
        $this->assertSame($expected, $result);
    }
}
