<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\ServiceReviewRepository;
use App\Entity\Php\ServiceReview;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceReviewRepositoryTest extends TestCase
{
    private ServiceReviewRepository&MockObject $repo;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(ServiceReviewRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testFindPositiveReviewsBuildsCorrectQuery(): void
    {
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.rating >= 4')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) {
                $this->assertSame('status', $name);
                $this->assertSame('PUBLISHED', $value);
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
            ->with('r')
            ->willReturn($this->qb);

        $result = $this->repo->findPositiveReviews();
        $this->assertIsArray($result);
    }

    public function testFindNeutralReviewsBuildsCorrectQuery(): void
    {
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.rating = 3')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) {
                $this->assertSame('status', $name);
                $this->assertSame('PUBLISHED', $value);
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
            ->with('r')
            ->willReturn($this->qb);

        $result = $this->repo->findNeutralReviews();
        $this->assertIsArray($result);
    }

    public function testFindNegativeReviewsBuildsCorrectQuery(): void
    {
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.rating <= 2')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) {
                $this->assertSame('status', $name);
                $this->assertSame('PUBLISHED', $value);
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
            ->with('r')
            ->willReturn($this->qb);

        $result = $this->repo->findNegativeReviews();
        $this->assertIsArray($result);
    }

    public function testGetAverageRatingByBusinessIdReturnsFloat(): void
    {
        $businessId = 'business-123';
        $avg = '4.25';

        $this->qb->expects($this->once())
            ->method('select')
            ->with('AVG(r.rating)')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.businessId = :businessId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($businessId) {
                if ($name === 'businessId') {
                    $this->assertSame($businessId, $value);
                } else {
                    $this->assertSame('PUBLISHED', $value);
                }
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($avg);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $result = $this->repo->getAverageRatingByBusinessId($businessId);
        $this->assertSame(4.25, $result);
    }

    public function testGetTotalReviewsByBusinessIdReturnsInt(): void
    {
        $businessId = 'business-456';
        $count = 10; // integer

        $this->qb->expects($this->once())
            ->method('select')
            ->with('COUNT(r.id)')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('where')
            ->with('r.businessId = :businessId')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use ($businessId) {
                if ($name === 'businessId') {
                    $this->assertSame($businessId, $value);
                } else {
                    $this->assertSame('PUBLISHED', $value);
                }
                return $this->qb;
            });
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $result = $this->repo->getTotalReviewsByBusinessId($businessId);
        $this->assertSame(10, $result);
        $this->assertIsInt($result);
    }
}
