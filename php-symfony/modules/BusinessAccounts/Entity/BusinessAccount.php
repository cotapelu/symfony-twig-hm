<?php

declare(strict_types=1);

namespace App\BusinessAccounts\Entity;

use App\Attribute\Module;

#[Module(name: 'BusinessAccounts', dependencies: ['User'])]
class BusinessAccount
{
    private ?int $id = null;
    private int $userId;
    private string $businessName;
    private string $businessType;
    private ?string $taxId = null;
    private ?string $address = null;
    private ?string $phone = null;
    private ?string $email = null;
    private ?string $whatsappBusinessId = null;
    private bool $isVerified = false;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(int $userId, string $businessName, string $businessType)
    {
        $this->userId = $userId;
        $this->businessName = $businessName;
        $this->businessType = $businessType;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters with proper return types
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function getBusinessName(): string { return $this->businessName; }
    public function setBusinessName(string $name): self { $this->businessName = $name; return $this; }
    public function getBusinessType(): string { return $this->businessType; }
    public function setBusinessType(string $type): self { $this->businessType = $type; return $this; }
    public function getTaxId(): ?string { return $this->taxId; }
    public function setTaxId(?string $taxId): self { $this->taxId = $taxId; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function getWhatsappBusinessId(): ?string { return $this->whatsappBusinessId; }
    public function setWhatsappBusinessId(?string $id): self { $this->whatsappBusinessId = $id; return $this; }
    public function isVerified(): bool { return $this->isVerified; }
    public function setVerified(bool $verified): self { $this->isVerified = $verified; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $dt): self { $this->createdAt = $dt; return $this; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $dt): self { $this->updatedAt = $dt; return $this; }
}
