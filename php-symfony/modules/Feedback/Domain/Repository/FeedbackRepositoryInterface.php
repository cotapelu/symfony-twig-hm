<?php

declare(strict_types=1);

namespace App\Feedback\Domain\Repository;

use App\Feedback\Domain\Entity\Feedback;

interface FeedbackRepositoryInterface
{
    public function findById(string $id): ?Feedback;

    public function findByNumber(string $number): ?Feedback;

    public function findByWard(string $wardCode, ?string $status = null, ?int $limit = null): array;

    public function findByCitizen(string $citizenId): array;

    public function findByPhone(string $phone): array;

    public function findPendingFeedback(): array;

    public function findOverdueFeedback(): array;

    public function save(Feedback $feedback): void;

    public function remove(Feedback $feedback): void;

    public function getStatistics(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array;

    public function getAverageRating(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): ?float;

    public function getDepartmentBreakdown(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array;
}
