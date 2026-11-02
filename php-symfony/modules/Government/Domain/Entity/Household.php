<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use App\Government\Domain\ValueObject\HouseholdId;
use App\Government\Domain\ValueObject\Address;

class Household
{
    private HouseholdId $id;
    private string $householdNumber;
    private Address $address;
    private string $headOfHouseholdId;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $dissolvedAt;
    private ?string $dissolutionReason;
    private bool $isActive = true;

    public function __construct(
        HouseholdId $id,
        string $householdNumber,
        Address $address,
        string $headOfHouseholdId
    ) {
        $this->id = $id;
        $this->householdNumber = $householdNumber;
        $this->address = $address;
        $this->headOfHouseholdId = $headOfHouseholdId;
        $this->createdAt = new DateTimeImmutable();
        $this->isActive = true;
    }

    public function getId(): HouseholdId
    {
        return $this->id;
    }

    public function getHouseholdNumber(): string
    {
        return $this->householdNumber;
    }

    public function setHouseholdNumber(string $householdNumber): void
    {
        $this->householdNumber = $householdNumber;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getHeadOfHouseholdId(): string
    {
        return $this->headOfHouseholdId;
    }

    public function setHeadOfHouseholdId(string $headOfHouseholdId): void
    {
        $this->headOfHouseholdId = $headOfHouseholdId;
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

    public function getDissolvedAt(): ?DateTimeImmutable
    {
        return $this->dissolvedAt;
    }

    public function setDissolvedAt(?DateTimeImmutable $dissolvedAt, ?string $reason = null): void
    {
        $this->dissolvedAt = $dissolvedAt;
        $this->dissolutionReason = $reason;
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getDissolutionReason(): ?string
    {
        return $this->dissolutionReason;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function dissolve(string $reason): void
    {
        $this->setDissolvedAt(new DateTimeImmutable(), $reason);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'householdNumber' => $this->householdNumber,
            'address' => $this->address->getFullAddress(),
            'headOfHouseholdId' => $this->headOfHouseholdId,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
