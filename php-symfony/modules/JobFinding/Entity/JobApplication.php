<?php

namespace App\JobFinding\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'job_applications')]
class JobApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    #[Assert\Positive]
    private int $jobId;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    #[Assert\Positive]
    private int $applicantId;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['pending', 'reviewed', 'interviewed', 'rejected', 'accepted', 'withdrawn'])]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $documents = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $appliedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(int $jobId, int $applicantId, ?string $coverLetter = null)
    {
        $this->jobId = $jobId;
        $this->applicantId = $applicantId;
        $this->coverLetter = $coverLetter;
        $this->status = 'pending';
        $this->appliedAt = new DateTimeImmutable();
    }

    public function getId() { return $this->id; }

    public function getJobId() { return $this->jobId; }
    public function setJobId(int $jobId) { $this->jobId = $jobId; return $this; }

    public function getApplicantId() { return $this->applicantId; }
    public function setApplicantId(int $applicantId) { $this->applicantId = $applicantId; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $allowed = ['pending', 'reviewed', 'interviewed', 'rejected', 'accepted', 'withdrawn'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid application status');
        }
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCoverLetter() { return $this->coverLetter; }
    public function setCoverLetter(?string $coverLetter)
    {
        $this->coverLetter = $coverLetter;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getDocuments() { return $this->documents; }
    public function setDocuments(?array $documents)
    {
        if ($documents !== null) {
            foreach ($documents as $doc) {
                if (!is_string($doc) || empty(trim($doc))) {
                    throw new \InvalidArgumentException('Each document must be a non-empty string (URL)');
                }
            }
            $this->documents = array_map('trim', $documents);
        } else {
            $this->documents = null;
        }
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function addDocument(string $document)
    {
        if (empty(trim($document))) {
            throw new \InvalidArgumentException('Document URL cannot be empty');
        }
        $this->documents[] = trim($document);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getNotes() { return $this->notes; }
    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getAppliedAt() { return $this->appliedAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getVersion() { return $this->version; }
}
