<?php declare(strict_types=1);

namespace App\Tests\Unit\JobFinding\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\JobFinding\Entity\JobApplication;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class JobApplicationTest extends TestCase
{
    private function createApplication(?string $coverLetter = null): JobApplication
    {
        return new JobApplication(jobId: 1, applicantId: 100, coverLetter: $coverLetter);
    }

    public function testConstructorInitializesProperties(): void
    {
        $app = $this->createApplication('Cover');
        $this->assertSame(1, $app->getJobId());
        $this->assertSame(100, $app->getApplicantId());
        $this->assertSame('pending', $app->getStatus());
        $this->assertSame('Cover', $app->getCoverLetter());
        $this->assertEmpty($app->getDocuments());
        $this->assertNull($app->getNotes());
        $this->assertInstanceOf(\DateTime::class, $app->getAppliedAt());
        $this->assertNull($app->getUpdatedAt());
    }

    public function testJobIdAndApplicantIdImmutableAfterConstruction(): void
    {
        $app = $this->createApplication(null);
        // No setters for these; they are set only via constructor.
        $this->assertSame(1, $app->getJobId());
        $this->assertSame(100, $app->getApplicantId());
    }

    public function testStatusTransitions(): void
    {
        $app = $this->createApplication();
        foreach (['pending','reviewed','interviewed','rejected','accepted','withdrawn'] as $status) {
            $app->setStatus($status);
            $this->assertSame($status, $app->getStatus());
        }
    }

    public function testUpdatedAtSetOnStatusChange(): void
    {
        $app = $this->createApplication();
        $this->assertNull($app->getUpdatedAt());
        $app->setStatus('reviewed');
        $this->assertInstanceOf(\DateTime::class, $app->getUpdatedAt());
    }

    public function testDocuments(): void
    {
        $app = $this->createApplication();
        $this->assertEmpty($app->getDocuments());

        $app->setDocuments(['resume.pdf', 'cert.jpg']);
        $this->assertSame(['resume.pdf', 'cert.jpg'], $app->getDocuments());

        $app->addDocument('ref.pdf');
        $this->assertContains('ref.pdf', $app->getDocuments());
    }

    public function testNotes(): void
    {
        $app = $this->createApplication();
        $app->setNotes('Some notes');
        $this->assertSame('Some notes', $app->getNotes());
        $app->setNotes(null);
        $this->assertNull($app->getNotes());
    }

    public function testIdSetters(): void
    {
        $app = $this->createApplication();
        $this->assertNull($app->getId());
        $app->setId(55);
        $this->assertSame(55, $app->getId());
    }
}
