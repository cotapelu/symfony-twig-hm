<?php

namespace App\Investment\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Investment\Repository\InvestmentRepository;
#[ORM\Entity(repositoryClass: InvestmentRepository::class)]
#[ORM\Table(name: 'investments')]
class Investment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $investmentType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private float $principal;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private float $currentValue;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4)]
    private float $returnRate;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'date')]
    private \DateTime $purchaseDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $maturityDate = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->currentValue = 0.00;
        $this->returnRate = 0.00;
        $this->currency = 'VND';
        $this->status = 'active';
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId)
    {
        $userId = trim($userId);
        if (empty($userId) || strlen($userId) > 255) {
            throw new \InvalidArgumentException('User ID must be 1-255 characters');
        }
        $this->userId = htmlspecialchars($userId, ENT_QUOTES, 'UTF-8');
        return $this;
    }

    public function getInvestmentType() { return $this->investmentType; }
    public function setInvestmentType(string $investmentType)
    {
        $investmentType = trim($investmentType);
        if (empty($investmentType)) {
            throw new \InvalidArgumentException('Investment type is required');
        }
        $validTypes = ['stock', 'bond', 'mutual_fund', 'etf', 'real_estate', 'crypto', 'savings', 'fixed_deposit'];
        if (!in_array($investmentType, $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid investment type. Valid: ' . implode(', ', $validTypes));
        }
        $this->investmentType = $investmentType;
        return $this;
    }

    public function getName() { return $this->name; }
    public function setName(string $name)
    {
        $name = trim($name);
        if (empty($name) || strlen($name) > 255) {
            throw new \InvalidArgumentException('Name must be 1-255 characters');
        }
        $this->name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return $this;
    }

    public function getPrincipal() { return $this->principal; }
    public function setPrincipal(float $principal)
    {
        if ($principal <= 0) {
            throw new \InvalidArgumentException('Principal must be positive');
        }
        $this->principal = $principal;
        return $this;
    }

    public function getCurrentValue() { return $this->currentValue; }
    public function setCurrentValue(float $currentValue)
    {
        if ($currentValue < 0) {
            throw new \InvalidArgumentException('Current value cannot be negative');
        }
        $this->currentValue = $currentValue;
        return $this;
    }

    public function getReturnRate() { return $this->returnRate; }
    public function setReturnRate(float $returnRate)
    {
        // Allow any float, but could cap at reasonable range if needed
        $this->returnRate = $returnRate;
        return $this;
    }

    public function calculateReturnRate() { 
        if ($this->principal > 0) {
            $this->returnRate = (($this->currentValue - $this->principal) / $this->principal) * 100;
        }
    }

    public function getCurrency() { return $this->currency; }
    public function setCurrency(string $currency)
    {
        $currency = trim($currency);
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be 3-letter ISO code');
        }
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function getPurchaseDate() { return $this->purchaseDate; }
    public function setPurchaseDate(\DateTime $purchaseDate)
    {
        $now = new \DateTime();
        if ($purchaseDate > $now) {
            throw new \InvalidArgumentException('Purchase date cannot be in the future');
        }
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getMaturityDate() { return $this->maturityDate; }
    public function setMaturityDate(?\DateTime $maturityDate)
    {
        if ($maturityDate !== null && $maturityDate <= $this->purchaseDate) {
            throw new \InvalidArgumentException('Maturity date must be after purchase date');
        }
        $this->maturityDate = $maturityDate;
        return $this;
    }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $validStatuses = ['active', 'matured', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException('Invalid status. Valid: ' . implode(', ', $validStatuses));
        }
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }

    public function getProfit() { return $this->currentValue - $this->principal; }

    public function isMatured()
    {
        if ($this->status !== 'active') {
            return $this->status === 'matured';
        }
        if ($this->maturityDate !== null) {
            $now = new \DateTime();
            return $now >= $this->maturityDate;
        }
        return false;
    }
}
