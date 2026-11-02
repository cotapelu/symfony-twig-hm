<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\CitizenId;

class PopulationChange
{
    private string $id;
    private CitizenId $citizenId;
    private string $changeType;
    private ?string $fromHouseholdId;
    private ?string $toHouseholdId;
    private ?string $fromAddress;
    private ?string $toAddress;
    private DateTimeImmutable $effectiveDate;
    private ?string $documentNumber;
    private ?DateTimeImmutable $documentDate;
    private string $status;
    private string $processedBy;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public const TYPE_BIRTH = 'birth';
    public const TYPE_DEATH = 'death';
    public const TYPE_MOVE_IN = 'move_in';
    public const TYPE_MOVE_OUT = 'move_out';
    public const TYPE_HOUSEHOLD_CHANGE = 'household_change';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        string $id,
        CitizenId $citizenId,
        string $changeType,
        DateTimeImmutable $effectiveDate,
        string $processedBy
    ) {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->changeType = $changeType;
        $this->effectiveDate = $effectiveDate;
        $this->processedBy = $processedBy;
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCitizenId(): CitizenId
    {
        return $this->citizenId;
    }

    public function getChangeType(): string
    {
        return $this->changeType;
    }

    public function setChangeType(string $changeType): void
    {
        $this->changeType = $changeType;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getFromHouseholdId(): ?string
    {
        return $this->fromHouseholdId;
    }

    public function setFromHouseholdId(?string $fromHouseholdId): void
    {
        $this->fromHouseholdId = $fromHouseholdId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getToHouseholdId(): ?string
    {
        return $this->toHouseholdId;
    }

    public function setToHouseholdId(?string $toHouseholdId): void
    {
        $this->toHouseholdId = $toHouseholdId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function setFromAddress(?string $fromAddress): void
    {
        $this->fromAddress = $fromAddress;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getToAddress(): ?string
    {
        return $this->toAddress;
    }

    public function setToAddress(?string $toAddress): void
    {
        $this->toAddress = $toAddress;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(DateTimeImmutable $effectiveDate): void
    {
        $this->effectiveDate = $effectiveDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(?string $documentNumber): void
    {
        $this->documentNumber = $documentNumber;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDocumentDate(): ?DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?DateTimeImmutable $documentDate): void
    {
        $this->documentDate = $documentDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getProcessedBy(): string
    {
        return $this->processedBy;
    }

    public function approve(): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
