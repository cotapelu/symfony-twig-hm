<?php

declare(strict_types=1);

namespace App\Feedback\Application\Service;

use App\Feedback\Domain\Entity\Feedback;
use App\Feedback\Domain\Repository\FeedbackRepositoryInterface;
use App\Government\Domain\Entity\Officer;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;

class FeedbackService
{
    public function __construct(
        private FeedbackRepositoryInterface $repository
    ) {}

    public function submitFeedback(
        string $type,
        string $wardCode,
        string $title,
        string $description,
        string $citizenName,
        ?string $citizenPhone = null,
        ?string $citizenEmail = null,
        ?string $department = null,
        ?string $serviceType = null,
        ?int $rating = null,
        ?array $attachments = null,
        bool $isAnonymous = false
    ): Feedback {
        $feedback = new Feedback(
            Uuid::uuid4()->toString(),
            $type,
            $wardCode,
            $title,
            $description,
            $citizenName,
            $citizenPhone,
            $citizenEmail,
            $rating
        );

        $feedback->setDepartment($department);
        $feedback->setServiceType($serviceType);
        $feedback->setAttachments($attachments);
        $feedback->setAnonymous($isAnonymous);

        if ($type === Feedback::TYPE_COMPLAINT) {
            $feedback->setPriority('high');
            
            $deadline = new DateTimeImmutable('+3 days');
            $feedback->setResponseDeadline($deadline);
        } elseif ($type === Feedback::TYPE_SERVICE_RATING) {
            if ($rating !== null && $rating < 3) {
                $feedback->setPriority('high');
                $deadline = new DateTimeImmutable('+2 days');
                $feedback->setResponseDeadline($deadline);
            }
        }

        $this->repository->save($feedback);
        return $feedback;
    }

    public function getFeedback(string $id): ?Feedback
    {
        return $this->repository->findById($id);
    }

    public function getFeedbackByNumber(string $number): ?Feedback
    {
        return $this->repository->findByNumber($number);
    }

    public function getFeedbackByWard(string $wardCode, ?string $status = null, ?int $limit = null): array
    {
        return $this->repository->findByWard($wardCode, $status, $limit);
    }

    public function getFeedbackByCitizen(string $citizenId): array
    {
        return $this->repository->findByCitizen($citizenId);
    }

    public function getFeedbackByPhone(string $phone): array
    {
        return $this->repository->findByPhone($phone);
    }

    public function getPendingFeedback(): array
    {
        return $this->repository->findPendingFeedback();
    }

    public function getOverdueFeedback(): array
    {
        return $this->repository->findOverdueFeedback();
    }

    public function assignFeedback(string $feedbackId, string $officerId): ?Feedback
    {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        $this->repository->save($feedback);

        return $feedback;
    }

    public function startProcessing(string $feedbackId): ?Feedback
    {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        $feedback->startProcessing();
        $this->repository->save($feedback);

        return $feedback;
    }

    public function resolveFeedback(
        string $feedbackId,
        string $resolutionSummary,
        string $resolvedBy
    ): ?Feedback {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        $feedback->resolve($resolutionSummary, $resolvedBy);
        $this->repository->save($feedback);

        return $feedback;
    }

    public function closeFeedback(string $feedbackId): ?Feedback
    {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        $feedback->close();
        $this->repository->save($feedback);

        return $feedback;
    }

    public function rejectFeedback(string $feedbackId, string $reason): ?Feedback
    {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        $feedback->reject($reason);
        $this->repository->save($feedback);

        return $feedback;
    }

    public function rateResolution(string $feedbackId, int $satisfaction): ?Feedback
    {
        $feedback = $this->repository->findById($feedbackId);

        if ($feedback === null) {
            return null;
        }

        if (!$feedback->isResolved()) {
            throw new \LogicException('Can only rate resolved feedback');
        }

        $feedback->setCitizenSatisfaction($satisfaction);
        $this->repository->save($feedback);

        return $feedback;
    }

    public function getStatistics(string $wardCode, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): array
    {
        return $this->repository->getStatistics($wardCode, $startDate, $endDate);
    }

    public function getAverageRating(string $wardCode, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): ?float
    {
        return $this->repository->getAverageRating($wardCode, $startDate, $endDate);
    }

    public function getDepartmentBreakdown(string $wardCode, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): array
    {
        return $this->repository->getDepartmentBreakdown($wardCode, $startDate, $endDate);
    }
}
