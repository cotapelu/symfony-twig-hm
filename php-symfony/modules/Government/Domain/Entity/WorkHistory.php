<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\OfficerId;

class WorkHistory
{
    private string $id;
    private OfficerId $officerId;
    private string $title;
    private string $description;
    private DateTimeImmutable $startDate;
    private ?DateTimeImmutable $endDate;
    private string $departmentCode;
    private string $positionCode;
    private ?string $notes;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        OfficerId $officerId,
        string $title,
        string $description,
        DateTimeImmutable $startDate,
        string $departmentCode,
        string $positionCode
    ) {
        $this->id = $id;
        $this->officerId = $officerId;
        $this->title = $title;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->departmentCode = $departmentCode;
        $this->positionCode = $positionCode;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getDepartmentCode(): string
    {
        return $this->departmentCode;
    }

    public function getPositionCode(): string
    {
        return $this->positionCode;
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
}
