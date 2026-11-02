<?php declare(strict_types=1);

namespace App\Tests\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\PaginationService;
use ArrayIterator;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaginationServiceTest extends TestCase
{
    private QueryBuilder&MockObject $qb;
    private Paginator&MockObject $paginator;

    protected function setUp(): void
    {
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->paginator = $this->createMock(Paginator::class);
    }

    private function createService(Paginator $paginator): PaginationService
    {
        return new class($paginator) extends PaginationService {
            private Paginator $mockPaginator;

            public function __construct(Paginator $mockPaginator)
            {
                $this->mockPaginator = $mockPaginator;
            }

            protected function createPaginator(QueryBuilder $qb): Paginator
            {
                return $this->mockPaginator;
            }
        };
    }

    public function testPaginateDefaultPageAndLimit(): void
    {
        $page = 1;
        $limit = 20;
        $offset = 0;
        $totalItems = 50;
        $totalPages = 3;

        $data = ['item1', 'item2'];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame($data, $result['data']);
        $this->assertSame($page, $result['meta']['current_page']);
        $this->assertSame($limit, $result['meta']['per_page']);
        $this->assertSame($totalItems, $result['meta']['total_items']);
        $this->assertSame($totalPages, $result['meta']['total_pages']);
    }

    public function testPaginateCustomPageAndLimit(): void
    {
        $page = 2;
        $limit = 10;
        $offset = 10;
        $totalItems = 15;
        $totalPages = 2;

        $data = ['a'];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame($data, $result['data']);
        $this->assertSame($page, $result['meta']['current_page']);
        $this->assertSame($limit, $result['meta']['per_page']);
        $this->assertSame($totalItems, $result['meta']['total_items']);
        $this->assertSame($totalPages, $result['meta']['total_pages']);
    }

    public function testPaginateClampsLimitToMax(): void
    {
        $page = 1;
        $limit = 200; // exceeds MAX_LIMIT 100
        $offset = 0;
        $clampedLimit = 100;
        $totalItems = 1000;
        $totalPages = 10;

        $data = [];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($clampedLimit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame($page, $result['meta']['current_page']);
        $this->assertSame($clampedLimit, $result['meta']['per_page']);
        $this->assertSame($totalItems, $result['meta']['total_items']);
        $this->assertSame($totalPages, $result['meta']['total_pages']);
    }

    public function testPaginateClampsPageToAtLeastOne(): void
    {
        $page = 0; // should become 1
        $limit = 20;
        $offset = 0;
        $totalItems = 5;
        $totalPages = 1;

        $data = ['x'];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame(1, $result['meta']['current_page']);
    }

    public function testPaginateTotalPagesCeiling(): void
    {
        $page = 1;
        $limit = 10;
        $offset = 0;
        $totalItems = 25; // 25/10 = 2.5 => 3 pages
        $totalPages = 3;

        $data = [1,2,3];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame($totalPages, $result['meta']['total_pages']);
    }

    public function testPaginateZeroTotalItems(): void
    {
        $page = 1;
        $limit = 20;
        $offset = 0;
        $totalItems = 0;
        $totalPages = 0; // ceil(0/20)=0

        $data = [];
        $iterator = new ArrayIterator($data);

        $this->qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturn($this->qb);
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturn($this->qb);
        $this->paginator->expects($this->exactly(2))
            ->method('count')
            ->willReturn($totalItems);
        $this->paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $service = $this->createService($this->paginator);
        $result = $service->paginate($this->qb, $page, $limit);

        $this->assertSame($data, $result['data']);
        $this->assertSame($totalItems, $result['meta']['total_items']);
        $this->assertSame($totalPages, $result['meta']['total_pages']);
    }
}
