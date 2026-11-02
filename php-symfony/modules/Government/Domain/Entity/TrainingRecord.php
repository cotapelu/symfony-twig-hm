<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\OfficerId;

class TrainingRecord
{
    private string $id;
    private OfficerId $officerId;
    private string $courseName;
    private string $trainingType;
    private string $provider;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private ?string $certificateUrl;
    private bool $isCompleted;
    private ?float $score;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        OfficerId $officerId,
        string $courseName,
        string $trainingType,
        string $provider,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ) {
        $this->id = $id;
        $this->officerId = $officerId;
        $this->courseName = $courseName;
        $this->trainingType = $trainingType;
        $this->provider = $provider;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->isCompleted = false;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getOfficerId(): OfficerId { return $this->officerId; }
    public function getCourseName(): string { return $this->courseName; }
    public function getTrainingType(): string { return $this->trainingType; }
    public function getProvider(): string { return $this->provider; }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): DateTimeImmutable { return $this->endDate; }
    public function getCertificateUrl(): ?string { return $this->certificateUrl; }
    public function setCertificateUrl(?string $url): void { $this->certificateUrl = $url; }
    public function isCompleted(): bool { return $this->isCompleted; }
    public function markAsCompleted(?float $score = null): void { $this->isCompleted = true; $this->score = $score; }
    public function getScore(): ?float { return $this->score; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
