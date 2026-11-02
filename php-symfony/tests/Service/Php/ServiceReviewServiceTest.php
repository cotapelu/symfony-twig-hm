<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ServiceReview;
use App\Repository\Php\ServiceReviewRepository;
use App\Service\Php\ServiceReviewService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceReviewServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ServiceReviewRepository&MockObject $repository;
    private ServiceReviewService $service;

    private string $reviewId;
    private ServiceReview $review;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ServiceReviewRepository::class);
        $this->service = new ServiceReviewService($this->em, $this->repository);

        $this->reviewId = bin2hex(random_bytes(16));
        $this->review = new ServiceReview(
            $this->reviewId,
            'business-1',
            'customer-1',
            5
        );
    }

    public function testCreateServiceReviewSuccess(): void
    {
        $businessId = 'biz-123';
        $customerId = 'cust-123';
        $rating = 4;
        $bookingId = 'booking-123';
        $comment = 'Great service!';
        $metadata = ['source' => 'web'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ServiceReview $sr) use (&$captured, $businessId, $customerId, $rating, $bookingId, $comment, $metadata) {
                $captured = $sr;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createServiceReview($businessId, $customerId, $rating, $bookingId, $comment, $metadata);
        $this->assertInstanceOf(ServiceReview::class, $result);
        $this->assertSame($businessId, $result->getBusinessId());
        $this->assertSame($customerId, $result->getCustomerId());
        $this->assertSame($rating, $result->getRating());
        $this->assertSame($bookingId, $result->getBookingId());
        $this->assertSame($comment, $result->getComment());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateServiceReviewValidationEmptyBusinessId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business ID is required');
        $this->service->createServiceReview('', 'cust', 3);
    }

    public function testCreateServiceReviewValidationEmptyCustomerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer ID is required');
        $this->service->createServiceReview('biz', '', 3);
    }

    public function testCreateServiceReviewValidationInvalidRating(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        $this->service->createServiceReview('biz', 'cust', 0);
    }

    public function testGetServiceReviewSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn($this->review);

        $result = $this->service->getServiceReview($this->reviewId);
        $this->assertSame($this->review, $result);
    }

    public function testGetServiceReviewNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ServiceReview not found');
        $this->service->getServiceReview($this->reviewId);
    }

    public function testUpdateServiceReviewSuccess(): void
    {
        $newRating = 2;
        $newComment = 'Updated comment';
        $newStatus = 'HIDDEN';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn($this->review);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'rating' => $newRating,
            'comment' => $newComment,
            'status' => $newStatus,
        ];
        $result = $this->service->updateServiceReview($this->reviewId, $data);
        $this->assertSame($this->review, $result);
        $this->assertSame($newRating, $result->getRating());
        $this->assertSame($newComment, $result->getComment());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateServiceReviewValidationInvalidRating(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn($this->review);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');
        $this->service->updateServiceReview($this->reviewId, ['rating' => 6]);
    }

    public function testUpdateServiceReviewValidationEmptyStatus(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn($this->review);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->updateServiceReview($this->reviewId, ['status' => '   ']);
    }

    public function testDeleteServiceReviewSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn($this->review);
        $this->em->expects($this->once())->method('remove')->with($this->review);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteServiceReview($this->reviewId);
    }

    public function testDeleteServiceReviewNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->reviewId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteServiceReview($this->reviewId);
    }

    public function testFindByBusinessId(): void
    {
        $businessId = 'biz-123';
        $results = [$this->review];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['businessId' => $businessId])
            ->willReturn($results);

        $result = $this->service->findByBusinessId($businessId);
        $this->assertSame($results, $result);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = 'cust-123';
        $results = [$this->review];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['customerId' => $customerId])
            ->willReturn($results);

        $result = $this->service->findByCustomerId($customerId);
        $this->assertSame($results, $result);
    }

    public function testFindByRating(): void
    {
        $rating = 5;
        $results = [$this->review];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['rating' => $rating])
            ->willReturn($results);

        $result = $this->service->findByRating($rating);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'APPROVED';
        $results = [$this->review];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }

    public function testGetAverageRating(): void
    {
        $businessId = 'biz-123';
        $average = '4.25';
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('AVG(r.rating)')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('r.businessId = :businessId')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('businessId', $businessId)->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn($average);

        $result = $this->service->getAverageRating($businessId);
        $this->assertSame(4.25, $result);
    }

    public function testGetAverageRatingWhenNull(): void
    {
        $businessId = 'biz-none';
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('AVG(r.rating)')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(false);

        $result = $this->service->getAverageRating($businessId);
        $this->assertNull($result);
    }
}
