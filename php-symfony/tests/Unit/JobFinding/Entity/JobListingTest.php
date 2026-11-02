<?php declare(strict_types=1);

namespace App\Tests\Unit\JobFinding\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\JobFinding\Entity\JobListing;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class JobListingTest extends TestCase
{
    private function createBasicListing(): JobListing
    {
        return new JobListing(
            employerId: 1,
            title: 'Developer',
            description: 'PHP developer needed',
            location: 'Remote',
            jobType: 'full-time',
            salaryMin: 50000,
            salaryMax: 70000
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $listing = $this->createBasicListing();
        $this->assertSame(1, $listing->getEmployerId());
        $this->assertSame('Developer', $listing->getTitle());
        $this->assertSame('PHP developer needed', $listing->getDescription());
        $this->assertSame('Remote', $listing->getLocation());
        $this->assertSame('full-time', $listing->getJobType());
        $this->assertEquals(50000, $listing->getSalaryMin());
        $this->assertEquals(70000, $listing->getSalaryMax());
        $this->assertSame('USD', $listing->getCurrency());
        $this->assertEmpty($listing->getRequirements());
        $this->assertEmpty($listing->getBenefits());
        $this->assertSame('active', $listing->getStatus());
        $this->assertInstanceOf(\DateTime::class, $listing->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $listing->getUpdatedAt());
        $this->assertNull($listing->getExpiresAt());
    }

    public function testGettersAndSetters(): void
    {
        $listing = $this->createBasicListing();

        $listing->setTitle('Senior Developer');
        $this->assertSame('Senior Developer', $listing->getTitle());

        $listing->setDescription('Advanced PHP and system design');
        $this->assertSame('Advanced PHP and system design', $listing->getDescription());

        $listing->setLocation('Hanoi');
        $this->assertSame('Hanoi', $listing->getLocation());

        $listing->setSalary(80000, 100000);
        $this->assertEquals(80000, $listing->getSalaryMin());
        $this->assertEquals(100000, $listing->getSalaryMax());

        $listing->setRequirements(['php', 'mysql', 'docker']);
        $this->assertSame(['php', 'mysql', 'docker'], $listing->getRequirements());

        $listing->setBenefits(['health insurance', 'remote']);
        $this->assertSame(['health insurance', 'remote'], $listing->getBenefits());

        $listing->setStatus('closed');
        $this->assertSame('closed', $listing->getStatus());

        $expires = new \DateTime('+1 year');
        $listing->setExpiresAt($expires);
        $this->assertSame($expires, $listing->getExpiresAt());
    }

    public function testSetSalaryUpdatesMinAndMax(): void
    {
        $listing = $this->createBasicListing();
        $listing->setSalary(60000, 90000);
        $this->assertEquals(60000, $listing->getSalaryMin());
        $this->assertEquals(90000, $listing->getSalaryMax());
    }
}
