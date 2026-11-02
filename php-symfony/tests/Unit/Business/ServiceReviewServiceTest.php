<?php

declare(strict_types=1);

namespace App\Tests\Unit\Business;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\ServiceReviewService;
use App\Entity\Php\ServiceReview;
use App\Repository\Php\ServiceReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

#[AllowMockObjectsWithoutExpectations]
class ServiceReviewServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ServiceReviewRepository $repository;
    private ServiceReviewService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ServiceReviewRepository::class);
        $this->service = new ServiceReviewService($this->em, $this->repository);
    }

    public function testCreateReview_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createServiceReview(
            'biz-123',
            'customer-456',
            5,
            'booking-789',
            'Great service!',
            ['source' => 'mobile']
        );

        $this->assertInstanceOf(ServiceReview::class, $result);
        $this->assertEquals('biz-123', $result->getBusinessId());
        $this->assertEquals('customer-456', $result->getCustomerId());
        $this->assertEquals(5, $result->getRating());
        $this->assertEquals('booking-789', $result->getBookingId());
        $this->assertEquals('PUBLISHED', $result->getStatus());
    }

    public function testCreateReview_InvalidRating_Low_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createServiceReview('biz', 'cust', 0);
    }

    public function testCreateReview_InvalidRating_High_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createServiceReview('biz', 'cust', 6);
    }

    public function testCreateReview_EmptyBusiness_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createServiceReview('', 'cust', 5);
    }

    public function testGetReview_Exists(): void
    {
        $review = new ServiceReview('id-1', 'biz-1', 'cust-1', 4);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($review);
        $result = $this->service->getServiceReview('id-1');
        $this->assertSame($review, $result);
    }

    public function testGetReview_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getServiceReview('missing');
    }

    public function testUpdateReview_Success(): void
    {
        $review = new ServiceReview('id-1', 'biz-1', 'cust-1', 3);
        $this->repository->expects($this->once())->method('find')->willReturn($review);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateServiceReview('id-1', [
            'rating' => 5,
            'comment' => 'Excellent!',
            'status' => 'HIDDEN',
        ]);

        $this->assertEquals(5, $result->getRating());
        $this->assertEquals('Excellent!', $result->getComment());
        $this->assertEquals('HIDDEN', $result->getStatus());
    }

    public function testUpdateReview_InvalidRating_ThrowsException(): void
    {
        $review = new ServiceReview('id-1', 'biz-1', 'cust-1', 3);
        $this->repository->expects($this->once())->method('find')->willReturn($review);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateServiceReview('id-1', ['rating' => 10]);
    }

    public function testUpdateReview_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateServiceReview('missing', ['rating' => 5]);
    }

    public function testDeleteReview_Success(): void
    {
        $review = new ServiceReview('id-1', 'biz-1', 'cust-1', 4);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($review);
        $this->em->expects($this->once())->method('remove')->with($review);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteServiceReview('id-1');
    }

    public function testFindByBusinessId_ReturnsArray(): void
    {
        $reviews = [new ServiceReview('id-1', 'biz-123', 'cust-1', 5)];
        $this->repository->expects($this->once())->method('findBy')->with(['businessId' => 'biz-123'])->willReturn($reviews);
        $result = $this->service->findByBusinessId('biz-123');
        $this->assertCount(1, $result);
    }

    public function testFindByCustomerId_ReturnsArray(): void
    {
        $reviews = [new ServiceReview('id-1', 'biz-1', 'cust-456', 4)];
        $this->repository->expects($this->once())->method('findBy')->with(['customerId' => 'cust-456'])->willReturn($reviews);
        $result = $this->service->findByCustomerId('cust-456');
        $this->assertCount(1, $result);
    }

    public function testFindByRating_ReturnsArray(): void
    {
        $reviews = [new ServiceReview('id-1', 'biz-1', 'cust-1', 5)];
        $this->repository->expects($this->once())->method('findBy')->with(['rating' => 5])->willReturn($reviews);
        $result = $this->service->findByRating(5);
        $this->assertCount(1, $result);
    }

    public function testGetAverageRating(): void
    {
        // We'll replace this with a proper mock of the query builder chain
        $qbMock = $this->createMock(QueryBuilder::class);
        $queryMock = $this->createMock(Query::class);

        // Set up the method chain: repository.createQueryBuilder('r') returns qbMock
        $this->repository->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qbMock);

        // qbMock: select('AVG(r.rating)') returns qbMock
        $qbMock->method('select')->with('AVG(r.rating)')->willReturn($qbMock);
        // where('r.businessId = :businessId') returns qbMock
        $qbMock->method('where')->with('r.businessId = :businessId')->willReturn($qbMock);
        // setParameter('businessId', 'biz-123') returns qbMock
        $qbMock->method('setParameter')->with('businessId', 'biz-123')->willReturn($qbMock);
        // getQuery() returns queryMock
        $qbMock->method('getQuery')->willReturn($queryMock);
        // getSingleScalarResult() returns '4.5'
        $queryMock->method('getSingleScalarResult')->willReturn('4.5');

        $result = $this->service->getAverageRating('biz-123');
        $this->assertSame(4.5, $result);
    }
}
