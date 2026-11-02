<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\OfficerId;

class PerformanceEvaluation
{
    private string $id;
    private OfficerId $officerId;
    private string $evaluationPeriod;
    private DateTimeImmutable $evaluationDate;
    private float $overallScore;
    private array $criteriaScores;
    private string $evaluatorId;
    private ?string $comments;
    private ?string $goals;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        OfficerId $officerId,
        string $evaluationPeriod,
        DateTimeImmutable $evaluationDate,
        float $overallScore,
        string $evaluatorId
    ) {
        $this->id = $id;
        $this->officerId = $officerId;
        $this->evaluationPeriod = $evaluationPeriod;
        $this->evaluationDate = $evaluationDate;
        $this->overallScore = $overallScore;
        $this->criteriaScores = [];
        $this->evaluatorId = $evaluatorId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getOfficerId(): OfficerId { return $this->officerId; }
    public function getEvaluationPeriod(): string { return $this->evaluationPeriod; }
    public function getEvaluationDate(): DateTimeImmutable { return $this->evaluationDate; }
    public function getOverallScore(): float { return $this->overallScore; }
    public function getCriteriaScores(): array { return $this->criteriaScores; }
    public function setCriteriaScores(array $scores): void { $this->criteriaScores = $scores; }
    public function getEvaluatorId(): string { return $this->evaluatorId; }
    public function getComments(): ?string { return $this->comments; }
    public function setComments(?string $comments): void { $this->comments = $comments; }
    public function getGoals(): ?string { return $this->goals; }
    public function setGoals(?string $goals): void { $this->goals = $goals; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
