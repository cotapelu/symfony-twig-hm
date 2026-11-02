<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\CitizenRepository;
use App\Entity\Php\Citizen;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenRepositoryTest extends TestCase
{
    private CitizenRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(CitizenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testSearchByNameBuildsCorrectQueryWithLimit(): void
    {
        $search = 'nguyen';
        $limit = 10;
        $expected = '%nguyen%';

        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('c.name LIKE :query')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($expected) {
                $this->assertSame('query', $name);
                $this->assertSame($expected, $value);
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($this->qb);

        $result = $this->repo->searchByName($search, $limit);
        $this->assertIsArray($result);
    }

    public function testSearchByNameUsesDefaultLimitWhenNotProvided(): void
    {
        $search = 'tran';

        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('c.name LIKE :query')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) {
                $this->assertSame('query', $name);
                $this->assertSame('%tran%', $value);
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($this->qb);

        $result = $this->repo->searchByName($search);
        $this->assertIsArray($result);
    }
}
