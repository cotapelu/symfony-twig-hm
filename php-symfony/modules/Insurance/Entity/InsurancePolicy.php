<?php

namespace App\Insurance\Entity;

use App\Attribute\Module;

#[Module(name: 'Insurance', dependencies: ['User'])]
class InsurancePolicy
{
    private ?int $id = null;
    private int $userId;
    private string $type; // health, life, car, property, travel
    private string $provider;
    private string $policyNumber;
    private float $coverageAmount;
    private float $premium;
    private string $currency;
    private \DateTime $startDate;
    private ?\DateTime $endDate = null;
    private ?string $status = 'pending';
    private ?string $beneficiary = null;

    public function __construct(int $userId, string $type, string $provider, string $policyNumber)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->provider = $provider;
        $this->policyNumber = $policyNumber;
        $this->currency = 'VND';
        $this->startDate = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getProvider() { return $this->provider; }
    public function getPolicyNumber() { return $this->policyNumber; }
    public function getCoverageAmount() { return $this->coverageAmount; }
    public function getPremium() { return $this->premium; }
    public function getCurrency() { return $this->currency; }
    public function getStartDate() { return $this->startDate; }
    public function getEndDate() { return $this->endDate; }
    public function getStatus() { return $this->status; }
    public function getBeneficiary() { return $this->beneficiary; }

    public function setCoverageAmount(float $amount)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Coverage amount must be positive');
        }
        $this->coverageAmount = $amount;
        return $this;
    }
    public function setPremium(float $premium)
    {
        if ($premium <= 0) {
            throw new \InvalidArgumentException('Premium must be positive');
        }
        $this->premium = $premium;
        return $this;
    }
    public function setEndDate(\DateTime $date)
    {
        if ($date < $this->startDate) {
            throw new \InvalidArgumentException('End date cannot be before start date');
        }
        $this->endDate = $date;
        return $this;
    }
    public function setBeneficiary(?string $name)
    {
        if ($name !== null) {
            $name = trim($name);
            if (strlen($name) > 255) {
                throw new \InvalidArgumentException('Beneficiary name cannot exceed 255 characters');
            }
            $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }
        $this->beneficiary = $name;
        return $this;
    }
    public function activate()
    {
        if ($this->status === 'cancelled') {
            throw new \LogicException('Cannot activate a cancelled policy');
        }
        $this->status = 'active';
        return $this;
    }
    public function cancel()
    {
        if ($this->status === 'cancelled') {
            return $this;
        }
        $this->status = 'cancelled';
        return $this;
    }

    public function isExpired()
    {
        if (!$this->endDate) return false;
        return new \DateTime() > $this->endDate;
    }

    public function isActive()
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
