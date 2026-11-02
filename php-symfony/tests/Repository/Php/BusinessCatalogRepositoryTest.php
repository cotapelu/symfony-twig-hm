<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\BusinessCatalogRepository;
use App\Entity\Php\BusinessCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessCatalogRepositoryTest extends TestCase
{
    private BusinessCatalogRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(BusinessCatalogRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testSearchByNameBuildsCorrectQuery(): void
    {
        $search = 'pizza';
        $expected = '%pizza%';

        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('bc.name LIKE :query')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($expected) {
                $this->assertSame('query', $name);
                $this->assertSame($expected, $value);
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
            ->with('bc')
            ->willReturn($this->qb);

        $result = $this->repo->searchByName($search);
        $this->assertIsArray($result);
    }
}
