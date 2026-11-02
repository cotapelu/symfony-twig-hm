<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\ServiceReview;
use App\Service\Php\ServiceReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ServiceReviewServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private ServiceReviewService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ServiceReviewService($this->em, $this->logger);
    }

    public function testCreateReviewSuccess(): void
    {
        $userId = 'user_123';
        $businessId = 'biz_456';
        $rating = 5;
        $comment = 'Excellent service!';
        $photos = ['photo1.jpg', 'photo2.jpg'];
        $metadata = ['visit_date' => '2025-06-01'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturn(null); // no existing review

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ServiceReview $r) use (
                $userId, $businessId, $rating, $comment, $photos, $metadata
            ) {
                return $r->getUserId() === $userId
                    && $r->getBusinessId() === $businessId
                    && $r->getRating() === $rating
                    && $r->getComment() === $comment
                    && $r->getPhotos() === $photos
                    && $r->getMetadata() === $metadata
                    && $r->getStatus() === 'PUBLISHED';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Service review created', $this->anything());

        $result = $this->service->createReview($userId, $businessId, $rating, $comment, $photos, $metadata);
        $this->assertInstanceOf(ServiceReview::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($businessId, $result->getBusinessId());
        $this->assertSame(5, $result->getRating());
        $this->assertSame('PUBLISHED', $result->getStatus());
    }

    public function testCreateReviewInvalidRatingLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createReview('user', 'biz', 0);
    }

    public function testCreateReviewInvalidRatingHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createReview('user', 'biz', 6);
    }

    public function testCreateReviewDuplicate(): void
    {
        $existing = new ServiceReview('id', 'user', 'biz', 5);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existing);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User has already reviewed this business');
        $this->service->createReview('user', 'biz', 5);
    }

    public function testGetReview(): void
    {
        $id = 'review_123';
        $review = new ServiceReview($id, 'user', 'biz', 5);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);

        $result = $this->service->getReview($id);
        $this->assertSame($review, $result);
    }

    public function testGetReviewsByBusiness(): void
    {
        $businessId = 'biz_123';
        $reviews = [
            new ServiceReview('id1', 'user1', $businessId, 5),
            new ServiceReview('id2', 'user2', $businessId, 4),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['businessId' => $businessId], ['createdAt' => 'DESC'])
            ->willReturn($reviews);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getReviewsByBusiness($businessId);
        $this->assertCount(2, $result);
    }

    public function testGetReviewsByUser(): void
    {
        $userId = 'user_123';
        $reviews = [
            new ServiceReview('id1', $userId, 'biz1', 5),
            new ServiceReview('id2', $userId, 'biz2', 3),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['createdAt' => 'DESC'])
            ->willReturn($reviews);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getReviewsByUser($userId);
        $this->assertCount(2, $result);
    }

    public function testGetUserBusinessReview(): void
    {
        $userId = 'user_123';
        $businessId = 'biz_456';
        $review = new ServiceReview('id', $userId, $businessId, 4);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId, 'businessId' => $businessId])
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUserBusinessReview($userId, $businessId);
        $this->assertSame($review, $result);
    }

    public function testUpdateReview(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 3);
        $newRating = 5;
        $newComment = 'Updated comment';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->updateReview($reviewId, $newRating, $newComment);
        $this->assertSame($review, $result);
        $this->assertSame($newRating, $result->getRating());
        $this->assertSame($newComment, $result->getComment());
    }

    public function testUpdateReviewNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->updateReview('nonexistent', 5);
        $this->assertNull($result);
    }

    public function testUpdateReviewInvalidRating(): void
    {
        $review = new ServiceReview('id', 'user', 'biz', 3);
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateReview('id', 6);
    }

    public function testDeleteReview(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 5);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($review);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->deleteReview($reviewId);
        $this->assertTrue($result);
    }

    public function testDeleteReviewNotFound(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->deleteReview('nonexistent');
        $this->assertFalse($result);
    }

    public function testGetAverageRatingByBusiness(): void
    {
        $businessId = 'biz_123';
        $average = 4.5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('AVG(r.rating)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('r.businessId = :businessId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $average);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);

        $result = $this->service->getAverageRatingByBusiness($businessId);
        $this->assertSame($average, $result);
    }

    public function testGetReviewCountByBusiness(): void
    {
        $businessId = 'biz_123';
        $count = 42;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(r.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('r.businessId = :businessId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);

        $result = $this->service->getReviewCountByBusiness($businessId);
        $this->assertSame($count, $result);
    }

    public function testGetRecentReviews(): void
    {
        $limit = 5;
        $reviews = [new ServiceReview('id', 'user', 'biz', 5)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('r.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($reviews);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getRecentReviews($limit);
        $this->assertCount(1, $result);
    }

    public function testGetLowRatingReviews(): void
    {
        $reviews = [new ServiceReview('id', 'user', 'biz', 1)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('r.rating <= :threshold')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('threshold', 2)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($reviews);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getLowRatingReviews();
        $this->assertCount(1, $result);
    }

    public function testFlagReview(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 5);
        $review->setStatus('PUBLISHED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Review flagged', $this->anything());

        $result = $this->service->flagReview($reviewId);
        $this->assertSame($review, $result);
        $this->assertSame('FLAGGED', $result->getStatus());
    }

    public function testUnflagReview(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 5);
        $review->setStatus('FLAGGED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->unflagReview($reviewId);
        $this->assertSame($review, $result);
        $this->assertSame('PUBLISHED', $result->getStatus());
    }

    public function testGetFlaggedReviews(): void
    {
        $reviews = [new ServiceReview('id', 'user', 'biz', 1)];
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'FLAGGED'])
            ->willReturn($reviews);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getFlaggedReviews();
        $this->assertCount(1, $result);
    }

    public function testRespondToReview(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 5);
        $response = 'Thank you for your feedback!';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->respondToReview($reviewId, $response);
        $this->assertSame($review, $result);
        $this->assertSame($response, $result->getResponse());
        $this->assertNotNull($result->getRespondedAt());
    }

    public function testGetRespondedReviews(): void
    {
        $reviews = [new ServiceReview('id', 'user', 'biz', 5)];
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['response' => null], ['createdAt' => 'DESC'])
            ->willReturn($reviews);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUnrespondedReviews();
        $this->assertCount(1, $result);
    }

    public function testGetUnrespondedReviews(): void
    {
        $reviews = [new ServiceReview('id', 'user', 'biz', 5)];
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['response' => null], ['createdAt' => 'DESC'])
            ->willReturn($reviews);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getUnrespondedReviews();
        $this->assertCount(1, $result);
    }

    public function testMarkHelpful(): void
    {
        $reviewId = 'review_123';
        $review = new ServiceReview($reviewId, 'user', 'biz', 5);
        $review->setHelpfulCount(0);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($reviewId)
            ->willReturn($review);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->markHelpful($reviewId);
        $this->assertSame($review, $result);
        $this->assertSame(1, $result->getHelpfulCount());
    }

    public function testGetReviewsByRating(): void
    {
        $rating = 5;
        $reviews = [new ServiceReview('id', 'user', 'biz', $rating)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('r.rating = :rating')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('r.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($reviews);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);

        $result = $this->service->getReviewsByRating($rating);
        $this->assertCount(1, $result);
    }

    public function testGetReviewsByDateRange(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime();
        $reviews = [new ServiceReview('id', 'user', 'biz', 5)];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('r.createdAt BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($reviews);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceReview::class)
            ->willReturn($repo);

        $result = $this->service->getReviewsByDateRange($start, $end);
        $this->assertCount(1, $result);
    }
}
