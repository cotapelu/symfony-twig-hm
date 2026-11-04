<?php

declare(strict_types=1);

namespace Tests\Unit\Feedback\Domain\Entity;

use App\Feedback\Domain\Entity\Feedback;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class FeedbackEntityTest extends TestCase
{
    private function makeFeedback(string $type = Feedback::TYPE_COMPLAINT): Feedback
    {
        return new Feedback(
            'fb-1',
            $type,
            'W01',
            'Mat nuoc',
            'Khu vuc Ngo 300 het nuoc 2 ngay',
            'Nguyen Van A',
            '0909123456',
            null,
            null
        );
    }

    // ================= constructor =================
    public function testConstructorInitializesState(): void
    {
        $f = $this->makeFeedback();

        $this->assertSame('fb-1', $f->getId());
        $this->assertSame(Feedback::TYPE_COMPLAINT, $f->getType());
        $this->assertSame('W01', $f->getWardCode());
        $this->assertSame('Mat nuoc', $f->getTitle());
        $this->assertSame('Nguyen Van A', $f->getCitizenName());
        $this->assertSame('0909123456', $f->getCitizenPhone());
        $this->assertSame(Feedback::STATUS_SUBMITTED, $f->getStatus());
        $this->assertStringStartsWith('PH-', $f->getFeedbackNumber());
        $this->assertFalse($f->isAnonymous());
        $this->assertFalse($f->isOverdue());
        $this->assertFalse($f->isResolved());
        $this->assertInstanceOf(DateTimeImmutable::class, $f->getCreatedAt());
        $this->assertNull($f->getDepartment());
        $this->assertNull($f->getResponseDeadline());
        $this->assertNull($f->getRating());
    }

    // ================= transitions =================
    public function testAssignToMovesToUnderReview(): void
    {
        $f = $this->makeFeedback();
        $f->assignTo('OFF-1');

        $this->assertSame(Feedback::STATUS_UNDER_REVIEW, $f->getStatus());
    }

    public function testStartProcessingFromUnderReview(): void
    {
        $f = $this->makeFeedback();
        $f->assignTo('OFF-1');
        $f->startProcessing();

        $this->assertSame(Feedback::STATUS_IN_PROGRESS, $f->getStatus());
    }

    public function testStartProcessingRejectsSubmitted(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can only start processing feedback in under_review status');
        $this->makeFeedback()->startProcessing();
    }

    public function testResolveSetsSummaryAndTimestamp(): void
    {
        $f = $this->makeFeedback();
        $f->resolve('Da sua xong ong nuoc', 'OFF-1');

        $this->assertSame(Feedback::STATUS_RESOLVED, $f->getStatus());
        $this->assertSame('Da sua xong ong nuoc', $f->toArray()['resolutionSummary'] ?? null);
        $this->assertTrue($f->isResolved());
    }

    public function testCloseSetsClosed(): void
    {
        $f = $this->makeFeedback();
        $f->close();

        $this->assertSame(Feedback::STATUS_CLOSED, $f->getStatus());
        $this->assertTrue($f->isResolved());
    }

    public function testRejectStoresReason(): void
    {
        $f = $this->makeFeedback();
        $f->reject('Khong thuoc tham quyen');

        $this->assertSame(Feedback::STATUS_REJECTED, $f->getStatus());
    }

    public function testSetCitizenSatisfactionAccepts1To5(): void
    {
        $f = $this->makeFeedback();
        $f->setCitizenSatisfaction(1);
        $f->setCitizenSatisfaction(5);

        $this->assertSame(5, $f->toArray()['citizenSatisfaction'] ?? null);
    }

    public function testSetCitizenSatisfactionRejectsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Satisfaction must be between 1 and 5');
        $this->makeFeedback()->setCitizenSatisfaction(6);
    }

    // ================= deadline / overdue logic =================
    public function testIsOverdueFalseWhenNoDeadline(): void
    {
        $this->assertFalse($this->makeFeedback()->isOverdue());
    }

    public function testIsOverdueFalseWhenDeadlineInFuture(): void
    {
        $f = $this->makeFeedback();
        $f->setResponseDeadline(new DateTimeImmutable('+2 days'));

        $this->assertFalse($f->isOverdue());
    }

    public function testIsOverdueTrueWhenDeadlinePassedAndOpen(): void
    {
        $f = $this->makeFeedback();
        $f->setResponseDeadline(new DateTimeImmutable('-1 day'));

        $this->assertTrue($f->isOverdue());
    }

    public function testIsOverdueFalseWhenResolved(): void
    {
        $f = $this->makeFeedback();
        $f->setResponseDeadline(new DateTimeImmutable('-1 day'));
        $f->resolve('xong', 'OFF-1');

        $this->assertFalse($f->isOverdue());
    }

    public function testIsOverdueFalseWhenClosed(): void
    {
        $f = $this->makeFeedback();
        $f->setResponseDeadline(new DateTimeImmutable('-1 day'));
        $f->close();

        $this->assertFalse($f->isOverdue());
    }

    // ================= setters / anonymity =================
    public function testSettersUpdateFields(): void
    {
        $f = $this->makeFeedback();
        $f->setTitle('Tieu de moi');
        $f->setDescription('Mo ta moi');
        $f->setDepartment('Ve sinh');
        $f->setServiceType('sua ong nuoc');
        $f->setPriority('urgent');
        $f->setRating(2);
        $f->setAttachments([['url' => 'https://x/y.jpg']]);
        $f->setAnonymous(true);

        $this->assertSame('Tieu de moi', $f->getTitle());
        $this->assertSame('Mo ta moi', $f->getDescription());
        $this->assertSame('Ve sinh', $f->getDepartment());
        $this->assertSame('sua ong nuoc', $f->getServiceType());
        $this->assertSame('urgent', $f->getPriority());
        $this->assertSame(2, $f->getRating());
        $this->assertSame([['url' => 'https://x/y.jpg']], $f->getAttachments());
        $this->assertTrue($f->isAnonymous());
    }

    public function testSettersAcceptNull(): void
    {
        $f = $this->makeFeedback();
        $f->setDepartment(null);
        $f->setServiceType(null);
        $f->setRating(null);
        $f->setAttachments(null);
        $f->setResponseDeadline(null);

        $this->assertNull($f->getDepartment());
        $this->assertNull($f->getServiceType());
        $this->assertNull($f->getRating());
        $this->assertNull($f->getAttachments());
        $this->assertNull($f->getResponseDeadline());
    }

    public function testToArraySerializesKeyFields(): void
    {
        $f = $this->makeFeedback();
        $arr = $f->toArray();

        $this->assertIsArray($arr);
        $this->assertSame('fb-1', $arr['id']);
        $this->assertSame('W01', $arr['wardCode']);
        $this->assertSame('Mat nuoc', $arr['title']);
        $this->assertSame(Feedback::STATUS_SUBMITTED, $arr['status']);
        $this->assertSame('Nguyen Van A', $arr['citizenName']);
    }
}
