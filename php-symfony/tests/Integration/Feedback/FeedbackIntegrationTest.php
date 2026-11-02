<?php

declare(strict_types=1);

namespace App\Tests\Integration\Feedback;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Feedback\Application\Service\FeedbackService;
use App\Feedback\Domain\Repository\FeedbackRepositoryInterface;
use App\Feedback\Domain\Entity\Feedback;
use App\Feedback\Infrastructure\Repository\FeedbackRepository;

#[AllowMockObjectsWithoutExpectations]
class FeedbackIntegrationTest extends DatabaseIntegrationTestCase
{
    private FeedbackService $feedbackService;
    private FeedbackRepositoryInterface $feedbackRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();
        // Manually construct FeedbackRepository as it extends EntityRepository
        $feedbackRepo = new FeedbackRepository($em, $em->getClassMetadata(Feedback::class));
        $this->feedbackService = new FeedbackService($feedbackRepo);
        $this->feedbackRepo = $feedbackRepo;
    }

    public function testSubmitFeedbackCreatesFeedback(): void
    {
        $type = Feedback::TYPE_SERVICE_RATING;
        $wardCode = 'W001';
        $title = 'Great service';
        $description = 'The staff were very helpful';
        $citizenName = 'Nguyễn Văn A';
        $citizenPhone = '0900111222';
        $rating = 5;

        $feedback = $this->feedbackService->submitFeedback(
            type: $type,
            wardCode: $wardCode,
            title: $title,
            description: $description,
            citizenName: $citizenName,
            citizenPhone: $citizenPhone,
            rating: $rating
        );

        $this->assertInstanceOf(Feedback::class, $feedback);
        $this->assertNotNull($feedback->getId());
        $this->assertSame($type, $feedback->getType());
        $this->assertSame($wardCode, $feedback->getWardCode());
        $this->assertSame($title, $feedback->getTitle());
        $this->assertSame($description, $feedback->getDescription());
        $this->assertSame($citizenName, $feedback->getCitizenName());
        $this->assertSame($citizenPhone, $feedback->getCitizenPhone());
        $this->assertSame($rating, $feedback->getRating());
        $this->assertSame('normal', $feedback->getPriority());
        $this->assertNull($feedback->getResponseDeadline());

        // Verify persisted
        $found = $this->feedbackRepo->findById($feedback->getId());
        $this->assertNotNull($found);
        $this->assertSame($feedback->getId(), $found->getId());
        $this->assertSame($feedback->getFeedbackNumber(), $found->getFeedbackNumber());
    }

    public function testComplaintGetsHighPriorityAndDeadline(): void
    {
        $feedback = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_COMPLAINT,
            wardCode: 'W002',
            title: 'Terrible experience',
            description: 'Long wait times',
            citizenName: 'Nguyễn Văn B'
        );

        $this->assertSame('high', $feedback->getPriority());
        $this->assertNotNull($feedback->getResponseDeadline());
        $this->assertGreaterThan(new \DateTimeImmutable(), $feedback->getResponseDeadline());
    }

    public function testServiceRatingBelow3GetsHighPriority(): void
    {
        $feedback = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_SERVICE_RATING,
            wardCode: 'W003',
            title: 'Okay',
            description: 'Could be better',
            citizenName: 'Nguyễn Văn C',
            rating: 2
        );

        $this->assertSame('high', $feedback->getPriority());
        $this->assertNotNull($feedback->getResponseDeadline());
        $this->assertGreaterThan(new \DateTimeImmutable(), $feedback->getResponseDeadline());
    }

    public function testGetFeedbackByWardReturnsOnlyMatching(): void
    {
        $wardA = 'W-A';
        $wardB = 'W-B';

        $fb1 = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_SUGGESTION,
            wardCode: $wardA,
            title: 'A1',
            description: 'Desc A1',
            citizenName: 'Citizen A1'
        );
        $fb2 = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_SUGGESTION,
            wardCode: $wardA,
            title: 'A2',
            description: 'Desc A2',
            citizenName: 'Citizen A2'
        );
        $fb3 = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_SUGGESTION,
            wardCode: $wardB,
            title: 'B1',
            description: 'Desc B1',
            citizenName: 'Citizen B1'
        );

        $wardsA = $this->feedbackService->getFeedbackByWard($wardA);
        $this->assertCount(2, $wardsA);
        $ids = array_map(fn($f) => $f->getId(), $wardsA);
        $this->assertContains($fb1->getId(), $ids);
        $this->assertContains($fb2->getId(), $ids);

        $wardsB = $this->feedbackService->getFeedbackByWard($wardB);
        $this->assertCount(1, $wardsB);
        $this->assertSame($fb3->getId(), $wardsB[0]->getId());
    }

    public function testGetFeedbackByNumber(): void
    {
        $feedback = $this->feedbackService->submitFeedback(
            type: Feedback::TYPE_TECHNICAL_ISSUE,
            wardCode: 'W004',
            title: 'Bug',
            description: 'App crash',
            citizenName: 'Citizen X'
        );

        $found = $this->feedbackService->getFeedbackByNumber($feedback->getFeedbackNumber());
        $this->assertNotNull($found);
        $this->assertSame($feedback->getId(), $found->getId());
        $this->assertSame($feedback->getFeedbackNumber(), $found->getFeedbackNumber());
    }
}
