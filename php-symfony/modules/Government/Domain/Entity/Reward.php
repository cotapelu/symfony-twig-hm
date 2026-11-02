<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\OfficerId;

class Reward
{
    public const TYPE_REWARD = 'reward';
    public const TYPE_DISCIPLINE = 'discipline';

    private string $id;
    private OfficerId $officerId;
    private string $type;
    private string $title;
    private string $description;
    private DateTimeImmutable $date;
    private string $decisionNumber;
    private ?string $issuedBy;
    private ?string $level;
    private ?string $notes;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        OfficerId $officerId,
        string $type,
        string $title,
        string $description,
        DateTimeImmutable $date,
        string $decisionNumber
    ) {
        $this->id = $id;
        $this->officerId = $officerId;
        $this->type = $type;
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->decisionNumber = $decisionNumber;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOfficerId(): OfficerId
    {
        return $this->officerId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getDecisionNumber(): string
    {
        return $this->decisionNumber;
    }

    public function getIssuedBy(): ?string
    {
        return $this->issuedBy;
    }

    public function setIssuedBy(?string $issuedBy): void
    {
        $this->issuedBy = $issuedBy;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): void
    {
        $this->level = $level;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isReward(): bool
    {
        return $this->type === self::TYPE_REWARD;
    }

    public function isDiscipline(): bool
    {
        return $this->type === self::TYPE_DISCIPLINE;
    }
}
