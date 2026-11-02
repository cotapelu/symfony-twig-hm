<?php declare(strict_types=1);
namespace App\Insurance\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Insurance\Repository\PolicyRepository;
use App\Insurance\Repository\ClaimRepository;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
#[ORM\Table(name: "insurance_policies")]
class Policy {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")] private int $id;    /** @phpstan-readonly */
    #[ORM\Column(type: "string", length: 50)] private string $policyNumber;
    #[ORM\Column(type: "string", length: 36)] private string $userId;
    #[ORM\Column(type: "string", length: 50)] private string $type;
    #[ORM\Column(type: "string", length: 100)] private string $provider;
    #[ORM\Column(type: "decimal", precision: 12, scale: 2)] private float $coverageAmount;
    #[ORM\Column(type: "decimal", precision: 12, scale: 2)] private float $premium;
    #[ORM\Column(type: "string", length: 20)] private string $status = 'active';
    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $endDate = null;
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $beneficiary = null;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->policyNumber = 'INS-' . strtoupper(bin2hex(random_bytes(6))); $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getPolicyNumber() { return $this->policyNumber; }
    public function setPolicyNumber(string $policyNumber) { $this->policyNumber = $policyNumber; return $this; }
    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; return $this; }
    public function getType() { return $this->type; }
    public function setType(string $type) { $this->type = $type; return $this; }
    public function getProvider() { return $this->provider; }
    public function setProvider(string $provider) { $this->provider = $provider; return $this; }
    public function getCoverageAmount() { return $this->coverageAmount; }
    public function setCoverageAmount(float $coverageAmount) { $this->coverageAmount = $coverageAmount; return $this; }
    public function getPremium() { return $this->premium; }
    public function setPremium(float $premium) { $this->premium = $premium; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getStartDate() { return $this->startDate; }
    public function setStartDate(\DateTime $startDate) { $this->startDate = $startDate; return $this; }
    public function getEndDate() { return $this->endDate; }
    public function setEndDate(?\DateTime $endDate) { $this->endDate = $endDate; return $this; }
    public function getBeneficiary() { return $this->beneficiary; }
    public function setBeneficiary(?string $beneficiary) { $this->beneficiary = $beneficiary; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->endDate === null) {
            return true;
        }
        return $this->endDate > new \DateTime();
    }
}

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
#[ORM\Table(name: "insurance_claims")]
class Claim {
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")] private ?int $id = null;
    #[ORM\Column(type: "string", length: 36)] private string $claimNumber;
    #[ORM\ManyToOne(targetEntity: Policy::class)] #[ORM\JoinColumn(name: "policy_id", referencedColumnName: "id")] private Policy $policy;
    #[ORM\Column(type: "decimal", precision: 12, scale: 2)] private float $claimAmount;
    #[ORM\Column(type: "string", length: 500, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "string", length: 20)] private string $status = self::STATUS_SUBMITTED;
    #[ORM\Column(type: "datetime", nullable: true)] private ?\DateTime $processedAt = null;
    #[ORM\Column(type: "datetime")] private \DateTime $createdAt;
    public function __construct() { $this->claimNumber = 'CLM-' . strtoupper(bin2hex(random_bytes(6))); $this->createdAt = new \DateTime(); }
    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }

    public function getClaimNumber() { return $this->claimNumber; }
    public function setClaimNumber(string $claimNumber) { $this->claimNumber = $claimNumber; return $this; }
    public function getPolicy() { return $this->policy; }
    public function setPolicy(Policy $policy) { $this->policy = $policy; return $this; }
    public function getClaimAmount() { return $this->claimAmount; }
    public function setClaimAmount(float $claimAmount) { $this->claimAmount = $claimAmount; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getProcessedAt() { return $this->processedAt; }
    public function setProcessedAt(?\DateTime $processedAt) { $this->processedAt = $processedAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
}
