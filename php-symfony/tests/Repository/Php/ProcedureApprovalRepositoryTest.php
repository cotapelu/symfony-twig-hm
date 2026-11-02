<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\ProcedureApprovalRepository;
use App\Entity\Php\ProcedureApproval;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApprovalRepositoryTest extends TestCase
{
    private ProcedureApprovalRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(ProcedureApprovalRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testFindLatestByApplicationIdBuildsCorrectQuery(): void
    {
        $appId = 'app-123';
        $expectedOrder = 'pa.createdAt DESC';
        $expectedLimit = 1;

        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('pa.applicationId = :applicationId')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($appId) {
                $this->assertSame('applicationId', $name);
                $this->assertSame($appId, $value);
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('pa.createdAt', 'DESC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setMaxResults')
            ->with($expectedLimit)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('pa')
            ->willReturn($this->qb);

        $result = $this->repo->findLatestByApplicationId($appId);
        $this->assertNull($result);
    }
}
