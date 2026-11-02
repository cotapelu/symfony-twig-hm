<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\DeliveryOrderItemRepository;
use App\Entity\Php\DeliveryOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeliveryOrderItemRepositoryTest extends TestCase
{
    private DeliveryOrderItemRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(DeliveryOrderItemRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testDeleteByOrderIdBuildsCorrectQueryAndExecutes(): void
    {
        $orderId = 'order-123';
        $affectedRows = 5;

        $this->qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('i.orderId = :orderId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('orderId', $orderId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($affectedRows);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('i')
            ->willReturn($this->qb);

        $result = $this->repo->deleteByOrderId($orderId);
        $this->assertSame($affectedRows, $result);
    }
}
