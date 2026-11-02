<?php

declare(strict_types=1);

namespace Tests\Unit\Feedback\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Feedback\Application\Service\FeedbackService;
use App\Feedback\Domain\Entity\Feedback;
use App\Feedback\Domain\Repository\FeedbackRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeedbackServiceTest extends TestCase
{
    private FeedbackRepositoryInterface&MockObject $repository;
    private FeedbackService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(FeedbackRepositoryInterface::class);
        $this->service = new FeedbackService($this->repository);
    }

    public function testSubmitFeedbackComplaintSetsHighPriorityAndDeadline(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $f) {
                return $f->getType() === Feedback::TYPE_COMPLAINT
                    && $f->getPriority() === 'high'
                    && $f->getResponseDeadline() !== null;
            }));

        $feedback = $this->service->submitFeedback(
            type: Feedback::TYPE_COMPLAINT,
            wardCode: 'W001',
            title: 'No water',
            description: 'description',
            citizenName: 'John'
        );

        $this->assertInstanceOf(Feedback::class, $feedback);
        $this->assertSame('high', $feedback->getPriority());
        $deadline = $feedback->getResponseDeadline();
        $this->assertInstanceOf(\DateTimeImmutable::class, $deadline);
        // Should be about +3 days
        $diff = $deadline->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();
        $this->assertGreaterThan(3*24*3600 - 60, $diff); // at least 3 days minus 1 min tolerance
    }

    public function testSubmitFeedbackServiceRatingLowSetsHighPriorityAnd2DayDeadline(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $f) {
                return $f->getType() === Feedback::TYPE_SERVICE_RATING
                    && $f->getRating() === 2
                    && $f->getPriority() === 'high';
            }));

        $feedback = $this->service->submitFeedback(
            type: Feedback::TYPE_SERVICE_RATING,
            wardCode: 'W001',
            title: 'Bad service',
            description: 'desc',
            citizenName: 'John',
            rating: 2
        );

        $this->assertSame(2, $feedback->getRating());
        $deadline = $feedback->getResponseDeadline();
        $this->assertInstanceOf(\DateTimeImmutable::class, $deadline);
        $diff = $deadline->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();
        $this->assertGreaterThan(2*24*3600 - 60, $diff);
    }

    public function testSubmitFeedbackServiceRatingGoodKeepsNormalPriorityAndNoDeadline(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $f) {
                return $f->getType() === Feedback::TYPE_SERVICE_RATING
                    && $f->getPriority() === 'normal'
                    && $f->getResponseDeadline() === null;
            }));

        $this->service->submitFeedback(
            type: Feedback::TYPE_SERVICE_RATING,
            wardCode: 'W001',
            title: 'Good',
            description: 'desc',
            citizenName: 'John',
            rating: 5
        );
    }

    public function testSubmitFeedbackSuggestionKeepsNormalAndNoDeadline(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Feedback $f) {
                return $f->getType() === Feedback::TYPE_SUGGESTION
                    && $f->getPriority() === 'normal'
                    && $f->getResponseDeadline() === null;
            }));

        $this->service->submitFeedback(
            type: Feedback::TYPE_SUGGESTION,
            wardCode: 'W001',
            title: 'Idea',
            description: 'desc',
            citizenName: 'John'
        );
    }

    public function testGetFeedbackFound(): void
    {
        $feedback = new Feedback(
            id: 'fb-123',
            type: Feedback::TYPE_COMPLAINT,
            wardCode: 'W001',
            title: 'Title',
            description: 'Desc',
            citizenName: 'John'
        );

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('fb-123')
            ->willReturn($feedback);

        $result = $this->service->getFeedback('fb-123');
        $this->assertSame($feedback, $result);
    }

    public function testGetFeedbackNotFoundReturnsNull(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('unknown')
            ->willReturn(null);

        $result = $this->service->getFeedback('unknown');
        $this->assertNull($result);
    }

    public function testGetFeedbackByNumber(): void
    {
        $feedback = $this->createMock(Feedback::class);
        $this->repository->expects($this->once())
            ->method('findByNumber')
            ->with('NUM123')
            ->willReturn($feedback);

        $result = $this->service->getFeedbackByNumber('NUM123');
        $this->assertSame($feedback, $result);
    }

    public function testGetFeedbackByWard(): void
    {
        $list = [new Feedback('1', Feedback::TYPE_SUGGESTION, 'W001', 'T', 'D', 'John')];
        $this->repository->expects($this->once())
            ->method('findByWard')
            ->with('W001', null, null)
            ->willReturn($list);

        $result = $this->service->getFeedbackByWard('W001');
        $this->assertCount(1, $result);
    }

    public function testGetPendingFeedback(): void
    {
        $pending = [$this->createMock(Feedback::class), $this->createMock(Feedback::class)];
        $this->repository->expects($this->once())
            ->method('findPendingFeedback')
            ->willReturn($pending);

        $result = $this->service->getPendingFeedback();
        $this->assertCount(2, $result);
    }
}
