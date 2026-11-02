<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\OfficerId;

class EmploymentContract
{
    private string $id;
    private OfficerId $officerId;
    private string $contractNumber;
    private string $contractType;
    private DateTimeImmutable $startDate;
    private ?DateTimeImmutable $endDate;
    private float $salary;
    private array $benefits;
    private ?string $attachmentUrl;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        OfficerId $officerId,
        string $contractNumber,
        string $contractType,
        DateTimeImmutable $startDate,
        float $salary
    ) {
        $this->id = $id;
        $this->officerId = $officerId;
        $this->contractNumber = $contractNumber;
        $this->contractType = $contractType;
        $this->startDate = $startDate;
        $this->salary = $salary;
        $this->benefits = [];
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getOfficerId(): OfficerId { return $this->officerId; }
    public function getContractNumber(): string { return $this->contractNumber; }
    public function getContractType(): string { return $this->contractType; }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): ?DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?DateTimeImmutable $endDate): void { $this->endDate = $endDate; $this->updatedAt = new DateTimeImmutable(); }
    public function getSalary(): float { return $this->salary; }
    public function setSalary(float $salary): void { $this->salary = $salary; $this->updatedAt = new DateTimeImmutable(); }
    public function getBenefits(): array { return $this->benefits; }
    public function setBenefits(array $benefits): void { $this->benefits = $benefits; $this->updatedAt = new DateTimeImmutable(); }
    public function getAttachmentUrl(): ?string { return $this->attachmentUrl; }
    public function setAttachmentUrl(?string $url): void { $this->attachmentUrl = $url; }
    public function isActive(): bool { return $this->isActive; }
    public function setActive(bool $active): void { $this->isActive = $active; $this->updatedAt = new DateTimeImmutable(); }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
