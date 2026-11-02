<?php

namespace App\JobFinding\Entity;

use App\Attribute\Module;

#[Module(name: 'JobFinding', dependencies: ['User'])]
class JobListing
{
    private ?int $id = null;
    private int $employerId;
    private string $title;
    private string $description;
    private string $location;
    private string $jobType;
    private float $salaryMin;
    private float $salaryMax;
    private string $currency = 'USD';
    private array $requirements = [];
    private array $benefits = [];
    private string $status = 'active';
    private \DateTime $createdAt;
    private \DateTime $updatedAt;
    private ?\DateTime $expiresAt = null;

    public function __construct(int $employerId, string $title, string $description, string $location, string $jobType, float $salaryMin, float $salaryMax)
    {
        $this->employerId = $employerId;
        $this->title = $title;
        $this->description = $description;
        $this->location = $location;
        $this->jobType = $jobType;
        $this->salaryMin = $salaryMin;
        $this->salaryMax = $salaryMax;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }
    public function getEmployerId() { return $this->employerId; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getLocation() { return $this->location; }
    public function getJobType() { return $this->jobType; }
    public function getSalaryMin() { return $this->salaryMin; }
    public function getSalaryMax() { return $this->salaryMax; }
    public function getCurrency() { return $this->currency; }
    public function getRequirements() { return $this->requirements; }
    public function getBenefits() { return $this->benefits; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getExpiresAt() { return $this->expiresAt; }

    public function setTitle(string $title) { $this->title = $title; $this->updatedAt = new \DateTime(); return $this; }
    public function setDescription(string $description) { $this->description = $description; $this->updatedAt = new \DateTime(); return $this; }
    public function setLocation(string $location) { $this->location = $location; $this->updatedAt = new \DateTime(); return $this; }
    public function setSalary(float $min, float $max) { $this->salaryMin = $min; $this->salaryMax = $max; $this->updatedAt = new \DateTime(); return $this; }
    public function setRequirements(array $requirements) { $this->requirements = $requirements; $this->updatedAt = new \DateTime(); return $this; }
    public function setBenefits(array $benefits) { $this->benefits = $benefits; $this->updatedAt = new \DateTime(); return $this; }
    public function setStatus(string $status) { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }
    public function setExpiresAt(?\DateTime $date) { $this->expiresAt = $date; return $this; }
}

#[Module(name: 'JobFinding', dependencies: ['User', 'JobFinding'])]
class JobApplication
{
    private ?int $id = null;
    private int $jobId;
    private int $applicantId;
    private string $status = 'pending';
    private ?string $coverLetter = null;
    private array $documents = [];
    private ?string $notes = null;
    private \DateTime $appliedAt;
    private ?\DateTime $updatedAt = null;

    public function __construct(int $jobId, int $applicantId, ?string $coverLetter = null)
    {
        $this->jobId = $jobId;
        $this->applicantId = $applicantId;
        $this->coverLetter = $coverLetter;
        $this->appliedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }
    public function getJobId() { return $this->jobId; }
    public function getApplicantId() { return $this->applicantId; }
    public function getStatus() { return $this->status; }
    public function getCoverLetter() { return $this->coverLetter; }
    public function getDocuments() { return $this->documents; }
    public function getNotes() { return $this->notes; }
    public function getAppliedAt() { return $this->appliedAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setStatus(string $status) { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }
    public function setDocuments(array $documents) { $this->documents = $documents; return $this; }
    public function addDocument(string $doc) { $this->documents[] = $doc; return $this; }
    public function setNotes(?string $notes) { $this->notes = $notes; return $this; }
}
