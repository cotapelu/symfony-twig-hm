<?php

namespace App\JobFinding\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'job_postings')]
class JobPosting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $employerId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $location;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['full-time', 'part-time', 'contract', 'internship', 'freelance'])]
    private string $jobType;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])]
    private string $experienceLevel;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    private ?float $salaryMin = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    private ?float $salaryMax = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    #[Assert\Length(3, 3)]
    private ?string $currency = 'VND';

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $requiredSkills = null;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $benefits = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private \DateTimeImmutable $applicationDeadline;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['active', 'closed', 'draft', 'expired'])]
    private string $status;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private int $viewCount = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private int $applicationCount = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->status = 'active';
        $this->viewCount = 0;
        $this->applicationCount = 0;
        $this->currency = 'VND';
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId() { return $this->id; }

    public function getEmployerId() { return $this->employerId; }
    public function setEmployerId(string $employerId)
    {
        if (empty(trim($employerId))) {
            throw new \InvalidArgumentException('Employer ID cannot be empty');
        }
        $this->employerId = trim($employerId);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getTitle() { return $this->title; }
    public function setTitle(string $title)
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }
        $this->title = trim($title);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getDescription() { return $this->description; }
    public function setDescription(string $description)
    {
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('Description cannot be empty');
        }
        $this->description = trim($description);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getLocation() { return $this->location; }
    public function setLocation(string $location)
    {
        if (empty(trim($location))) {
            throw new \InvalidArgumentException('Location cannot be empty');
        }
        $this->location = trim($location);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getJobType() { return $this->jobType; }
    public function setJobType(string $jobType)
    {
        $allowed = ['full-time', 'part-time', 'contract', 'internship', 'freelance'];
        if (!in_array($jobType, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid job type');
        }
        $this->jobType = $jobType;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getExperienceLevel() { return $this->experienceLevel; }
    public function setExperienceLevel(string $experienceLevel)
    {
        $allowed = ['entry', 'junior', 'mid', 'senior', 'lead', 'executive'];
        if (!in_array($experienceLevel, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid experience level');
        }
        $this->experienceLevel = $experienceLevel;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getSalaryMin() { return $this->salaryMin; }
    public function setSalaryMin(?float $salaryMin)
    {
        if ($salaryMin !== null && $salaryMin < 0) {
            throw new \InvalidArgumentException('Salary minimum cannot be negative');
        }
        $this->salaryMin = $salaryMin;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getSalaryMax() { return $this->salaryMax; }
    public function setSalaryMax(?float $salaryMax)
    {
        if ($salaryMax !== null && $salaryMax < 0) {
            throw new \InvalidArgumentException('Salary maximum cannot be negative');
        }
        if ($this->salaryMin !== null && $salaryMax !== null && $salaryMax < $this->salaryMin) {
            throw new \InvalidArgumentException('Salary maximum cannot be less than minimum');
        }
        $this->salaryMax = $salaryMax;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCurrency() { return $this->currency; }
    public function setCurrency(?string $currency)
    {
        if ($currency !== null && !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency must be 3 uppercase letters (ISO 4217)');
        }
        $this->currency = $currency;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getRequiredSkills() { return $this->requiredSkills; }
    public function setRequiredSkills(?array $requiredSkills)
    {
        if ($requiredSkills !== null) {
            if (count($requiredSkills) > 100) {
                throw new \InvalidArgumentException('Required skills array too large (max 100)');
            }
            foreach ($requiredSkills as $skill) {
                if (!is_string($skill) || empty(trim($skill))) {
                    throw new \InvalidArgumentException('Each skill must be a non-empty string');
                }
            }
            $this->requiredSkills = array_map('trim', $requiredSkills);
        }
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getBenefits() { return $this->benefits; }
    public function setBenefits(?array $benefits)
    {
        if ($benefits !== null) {
            if (count($benefits) > 50) {
                throw new \InvalidArgumentException('Benefits array too large (max 50)');
            }
            foreach ($benefits as $benefit) {
                if (!is_string($benefit) || empty(trim($benefit))) {
                    throw new \InvalidArgumentException('Each benefit must be a non-empty string');
                }
            }
            $this->benefits = array_map('trim', $benefits);
        }
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getApplicationDeadline() { return $this->applicationDeadline; }
    public function setApplicationDeadline(\DateTime $applicationDeadline)
    {
        $deadline = DateTimeImmutable::createFromInterface($applicationDeadline);
        if ($deadline < new DateTimeImmutable()) {
            throw new \InvalidArgumentException('Application deadline cannot be in the past');
        }
        $this->applicationDeadline = $deadline;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $allowed = ['active', 'closed', 'draft', 'expired'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getViewCount() { return $this->viewCount; }
    public function incrementViewCount()
    {
        $this->viewCount++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getApplicationCount() { return $this->applicationCount; }
    public function incrementApplicationCount()
    {
        $this->applicationCount++;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getVersion() { return $this->version; }
}
