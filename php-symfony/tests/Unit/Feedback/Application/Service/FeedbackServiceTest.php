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

    // ================= state machine (service layer) =================
    public function testAssignFeedback_AssignsOfficerAndMovesToUnderReview(): void
    {
        $feedback = new Feedback(
            'fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A'
        );
        $this->repository->method('findById')->with('fb-1')->willReturn($feedback);
        $this->repository->expects($this->once())->method('save')->with($feedback);

        $result = $this->service->assignFeedback('fb-1', 'OFF-7');

        // The service must actually assign: officer recorded + status advanced.
        // (Previously $officerId was dropped — no assignTo() call — a silent bug.)
        $this->assertSame($feedback, $result);
        $this->assertSame(Feedback::STATUS_UNDER_REVIEW, $feedback->getStatus());
    }

    public function testAssignFeedback_NotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        $this->assertNull($this->service->assignFeedback('nope', 'OFF-1'));
    }

    public function testStartProcessing_NotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        $this->assertNull($this->service->startProcessing('nope'));
    }

    public function testStartProcessing_Success(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $feedback->assignTo('OFF-1');
        $this->repository->method('findById')->willReturn($feedback);

        $result = $this->service->startProcessing('fb-1');

        $this->assertSame(Feedback::STATUS_IN_PROGRESS, $result->getStatus());
    }

    public function testResolveFeedback_Success(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $this->repository->method('findById')->willReturn($feedback);

        $result = $this->service->resolveFeedback('fb-1', 'Da xu ly', 'OFF-1');

        $this->assertTrue($result->isResolved());
    }

    public function testResolveFeedback_NotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->assertNull($this->service->resolveFeedback('nope', 's', 'OFF-1'));
    }

    public function testCloseFeedback_Success(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $this->repository->method('findById')->willReturn($feedback);

        $this->assertSame(
            Feedback::STATUS_CLOSED,
            $this->service->closeFeedback('fb-1')->getStatus()
        );
    }

    public function testRejectFeedback_Success(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $this->repository->method('findById')->willReturn($feedback);

        $this->assertSame(
            Feedback::STATUS_REJECTED,
            $this->service->rejectFeedback('fb-1', 'khong thuoc tham quyen')->getStatus()
        );
    }

    public function testRateResolution_RejectsOutOfRange(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $feedback->resolve('xong', 'OFF-1'); // service guards: must be resolved first
        $this->repository->method('findById')->willReturn($feedback);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->rateResolution('fb-1', 9);
    }

    public function testRateResolution_RejectsUnresolved(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $this->repository->method('findById')->willReturn($feedback);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only rate resolved feedback');
        $this->service->rateResolution('fb-1', 5);
    }

    public function testRateResolution_Success(): void
    {
        $feedback = new Feedback('fb-1', Feedback::TYPE_COMPLAINT, 'W01', 'T', 'D', 'A');
        $feedback->resolve('xong', 'OFF-1');
        $this->repository->method('findById')->willReturn($feedback);
        $this->repository->expects($this->once())->method('save')->with($feedback);

        $this->assertSame($feedback, $this->service->rateResolution('fb-1', 4));
        $this->assertSame(4, $feedback->toArray()['citizenSatisfaction'] ?? null);
    }

    public function testGetOverdueFeedback_Delegates(): void
    {
        $this->repository->expects($this->once())->method('findOverdueFeedback')->willReturn([]);
        $this->assertSame([], $this->service->getOverdueFeedback());
    }

    public function testGetStatistics_Delegates(): void
    {
        $this->repository->expects($this->once())->method('getStatistics')
            ->with('W01', null, null)->willReturn(['total' => 3]);

        $this->assertSame(['total' => 3], $this->service->getStatistics('W01'));
    }

    public function testGetAverageRating_NullWhenNoRatings(): void
    {
        $this->repository->method('getAverageRating')->willReturn(null);
        $this->assertNull($this->service->getAverageRating('W01'));
    }

    public function testGetDepartmentBreakdown_Delegates(): void
    {
        $breakdown = [['department' => 'Ve sinh', 'count' => 2, 'avgRating' => 4.0]];
        $this->repository->method('getDepartmentBreakdown')->willReturn($breakdown);

        $this->assertSame($breakdown, $this->service->getDepartmentBreakdown('W01'));
    }
}
