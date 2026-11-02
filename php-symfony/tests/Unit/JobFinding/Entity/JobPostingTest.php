<?php declare(strict_types=1);

namespace App\Tests\Unit\JobFinding\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\JobFinding\Entity\JobPosting;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class JobPostingTest extends TestCase
{
    private function createBasicJobPosting(): JobPosting
    {
        $job = new JobPosting();
        $job->setEmployerId('employer-1')
            ->setTitle('Test Job')
            ->setDescription('Test description')
            ->setLocation('Hanoi')
            ->setJobType('full-time')
            ->setSalaryMin(1000)
            ->setSalaryMax(2000)
            ->setApplicationDeadline(new \DateTime('+30 days'))
            ->setExperienceLevel('mid')
            ->setStatus('active');
        return $job;
    }

    public function testConstructorInitializesDefaults(): void
    {
        $job = new JobPosting();
        $this->assertNull($job->getId());
        $this->assertSame('active', $job->getStatus());
        $this->assertSame(0, $job->getViewCount());
        $this->assertSame(0, $job->getApplicationCount());
        $this->assertSame('VND', $job->getCurrency());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getUpdatedAt());
        $this->assertSame(1, $job->getVersion());
    }

    public function testGettersAndSetters(): void
    {
        $job = $this->createBasicJobPosting();

        $this->assertSame('employer-1', $job->getEmployerId());
        $this->assertSame('Test Job', $job->getTitle());
        $this->assertSame('Test description', $job->getDescription());
        $this->assertSame('Hanoi', $job->getLocation());
        $this->assertSame('full-time', $job->getJobType());
        $this->assertEquals(1000, $job->getSalaryMin());
        $this->assertEquals(2000, $job->getSalaryMax());
        $this->assertSame('mid', $job->getExperienceLevel());
        $this->assertSame('active', $job->getStatus());
        $this->assertSame(0, $job->getViewCount());
    }

    public function testValidationEmployerId(): void
    {
        $job = new JobPosting();
        $job->setEmployerId('emp');
        $this->assertSame('emp', $job->getEmployerId());

        $this->expectException(\InvalidArgumentException::class);
        $job->setEmployerId('   ');
    }

    public function testValidationTitle(): void
    {
        $job = new JobPosting();
        $job->setTitle('New Title');
        $this->assertSame('New Title', $job->getTitle());

        $this->expectException(\InvalidArgumentException::class);
        $job->setTitle('');
    }

    public function testValidationDescription(): void
    {
        $job = new JobPosting();
        $job->setDescription('Desc');
        $this->assertSame('Desc', $job->getDescription());

        $this->expectException(\InvalidArgumentException::class);
        $job->setDescription('   ');
    }

    public function testValidationLocation(): void
    {
        $job = new JobPosting();
        $job->setLocation('SG');
        $this->assertSame('SG', $job->getLocation());

        $this->expectException(\InvalidArgumentException::class);
        $job->setLocation("\t\n");
    }

    public function testValidationJobType(): void
    {
        $job = new JobPosting();
        $allowed = ['full-time', 'part-time', 'contract', 'internship', 'freelance'];
        foreach ($allowed as $type) {
            $job->setJobType($type);
            $this->assertSame($type, $job->getJobType());
        }
        $this->expectException(\InvalidArgumentException::class);
        $job->setJobType('unknown');
    }

    public function testValidationExperienceLevel(): void
    {
        $job = new JobPosting();
        $allowed = ['entry', 'junior', 'mid', 'senior', 'lead', 'executive'];
        foreach ($allowed as $level) {
            $job->setExperienceLevel($level);
            $this->assertSame($level, $job->getExperienceLevel());
        }
        $this->expectException(\InvalidArgumentException::class);
        $job->setExperienceLevel('unknown');
    }

    public function testSalaryMinAndMax(): void
    {
        $job = new JobPosting();

        $job->setSalaryMin(500);
        $this->assertEquals(500, $job->getSalaryMin());

        $job->setSalaryMax(1000);
        $this->assertEquals(1000, $job->getSalaryMax());

        $job->setSalaryMin(1500)->setSalaryMax(2500);
        $this->assertEquals(1500, $job->getSalaryMin());
        $this->assertEquals(2500, $job->getSalaryMax());

        // Negative should throw
        $this->expectException(\InvalidArgumentException::class);
        $job->setSalaryMin(-100);
    }

    public function testSalaryMaxLessThanMinThrows(): void
    {
        $job = new JobPosting();
        $job->setSalaryMin(2000);

        $this->expectException(\InvalidArgumentException::class);
        $job->setSalaryMax(1000);
    }

    public function testCurrency(): void
    {
        $job = new JobPosting();
        $this->assertSame('VND', $job->getCurrency());

        $job->setCurrency('USD');
        $this->assertSame('USD', $job->getCurrency());

        $this->expectException(\InvalidArgumentException::class);
        $job->setCurrency('abcd'); // not 3 uppercase letters
    }

    public function testRequiredSkillsAndBenefits(): void
    {
        $job = new JobPosting();
        $this->assertEmpty($job->getRequiredSkills());
        $this->assertEmpty($job->getBenefits());

        $skills = ['php', 'mysql', 'redis'];
        $job->setRequiredSkills($skills);
        $this->assertSame($skills, $job->getRequiredSkills());

        $benefits = ['health', 'mobile'];
        $job->setBenefits($benefits);
        $this->assertSame($benefits, $job->getBenefits());

        // Limit checks
        $big = array_fill(0, 101, 'skill');
        $this->expectException(\InvalidArgumentException::class);
        $job->setRequiredSkills($big);
    }

    public function testApplicationDeadline(): void
    {
        $job = new JobPosting();
        // Set with mutable DateTime; setter converts to immutable
        $deadline = new \DateTime('+10 days');
        $job->setApplicationDeadline($deadline);
        $expected = \DateTimeImmutable::createFromInterface($deadline);
        $this->assertEquals($expected, $job->getApplicationDeadline());

        // Past deadline should throw
        $past = new \DateTime('-1 day');
        $this->expectException(\InvalidArgumentException::class);
        $job->setApplicationDeadline($past);
    }

    public function testStatus(): void
    {
        $job = new JobPosting();
        foreach (['active', 'closed', 'draft', 'expired'] as $s) {
            $job->setStatus($s);
            $this->assertSame($s, $job->getStatus());
        }
        $this->expectException(\InvalidArgumentException::class);
        $job->setStatus('invalid');
    }

    public function testViewCountIncrement(): void
    {
        $job = $this->createBasicJobPosting();
        $this->assertSame(0, $job->getViewCount());
        $job->incrementViewCount();
        $this->assertSame(1, $job->getViewCount());
    }

    public function testUpdatedAtChangesOnMutation(): void
    {
        $job = $this->createBasicJobPosting();
        $original = $job->getUpdatedAt();
        // Wait a bit? Not needed; just ensure setter updates
        $job->setTitle('New Title');
        $this->assertNotEquals($original, $job->getUpdatedAt());
    }

    public function testVersionInitial(): void
    {
        $job = $this->createBasicJobPosting();
        $this->assertSame(1, $job->getVersion());
    }
}
