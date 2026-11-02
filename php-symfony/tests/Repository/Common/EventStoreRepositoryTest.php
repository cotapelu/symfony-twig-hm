<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\EventStoreRepository;
use App\Entity\Common\EventStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EventStoreRepositoryTest extends TestCase
{
    private EventStoreRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(EventStoreRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testFindLatestVersionBuildsCorrectQuery(): void
    {
        $aggregateId = 'agg-123';
        $expected = $this->createMock(EventStore::class);

        $this->qb->expects($this->once())
            ->method('where')
            ->with('e.aggregateId = :aggregateId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('e.version', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('aggregateId', $aggregateId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expected);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->qb);

        $result = $this->repo->findLatestVersion($aggregateId);
        $this->assertSame($expected, $result);
    }

    public function testFindByDateRangeBuildsCorrectQuery(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime();

        $this->qb->expects($this->once())
            ->method('where')
            ->with('e.createdAt >= :start')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('e.createdAt <= :end')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('e.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, \DateTime $value) use ($start, $end) {
                if ($name === 'start') {
                    $this->assertEquals($start, $value);
                } else {
                    $this->assertEquals($end, $value);
                }
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->qb);

        $result = $this->repo->findByDateRange($start, $end);
        $this->assertIsArray($result);
    }

    public function testGetMaxVersionReturnsInt(): void
    {
        $aggregateId = 'agg-456';
        $maxVersion = '5'; // scalar string

        $this->qb->expects($this->once())
            ->method('select')
            ->with('MAX(e.version)')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('e.aggregateId = :aggregateId')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->with('aggregateId', $aggregateId)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($maxVersion);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->qb);

        $result = $this->repo->getMaxVersion($aggregateId);
        $this->assertSame(5, $result);
        $this->assertIsInt($result);
    }
}
