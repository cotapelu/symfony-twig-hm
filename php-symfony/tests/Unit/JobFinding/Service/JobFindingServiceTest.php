<?php declare(strict_types=1);

namespace App\Tests\Unit\JobFinding\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\JobFinding\Entity\JobApplication;
use App\JobFinding\Entity\JobPosting;
use App\JobFinding\Repository\JobApplicationRepository;
use App\JobFinding\Repository\JobPostingRepository;
use App\JobFinding\Service\JobFindingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class JobFindingServiceTest extends TestCase
{
    private EntityManagerInterface $emMock;
    private JobPostingRepository $jobRepoMock;
    private JobApplicationRepository $applicationRepoMock;
    private JobFindingService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->jobRepoMock = $this->createMock(JobPostingRepository::class);
        $this->applicationRepoMock = $this->createMock(JobApplicationRepository::class);

        $this->service = new JobFindingService(
            $this->emMock,
            $this->jobRepoMock,
            $this->applicationRepoMock
        );
    }

    public function testCreateJobListingSuccess(): void
    {
        $data = [
            'title' => 'Test Job',
            'description' => 'A test job',
            'location' => 'Hanoi',
            'job_type' => 'full-time',
            'experience_level' => 'entry',
            'salary_min' => 1000,
            'salary_max' => 2000,
            'currency' => 'USD',
            'required_skills' => ['PHP', 'MySQL'],
            'benefits' => ['remote'],
            'application_deadline' => (new \DateTime('+1 week'))->format('Y-m-d'),
        ];
        $employerId = 'employer-1';

        // Expect persist and flush
        $this->emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(JobPosting::class));
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->createJobListing($data, $employerId);

        $this->assertInstanceOf(JobPosting::class, $result);
        $this->assertSame('Test Job', $result->getTitle());
        $this->assertSame($employerId, $result->getEmployerId());
    }

    public function testCreateJobListingMissingDeadlineThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Application deadline is required');

        $data = [
            'title' => 'Test Job',
            'description' => 'A test job',
            'location' => 'Hanoi',
            'job_type' => 'full-time',
            'experience_level' => 'entry',
            'salary_min' => 1000,
            'salary_max' => 2000,
            // missing application_deadline
        ];
        $this->service->createJobListing($data, 'emp1');
    }

    public function testGetJobListingFound(): void
    {
        $job = new JobPosting();
        $this->jobRepoMock->expects($this->once())->method('find')->with(123)->willReturn($job);

        $result = $this->service->getJobListing(123);
        $this->assertSame($job, $result);
    }

    public function testGetJobListingNotFound(): void
    {
        $this->jobRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);
        $result = $this->service->getJobListing(999);
        $this->assertNull($result);
    }

    public function testGetActiveJobListings(): void
    {
        $jobs = [new JobPosting(), new JobPosting()];
        $this->jobRepoMock->expects($this->once())->method('findActive')->willReturn($jobs);
        $result = $this->service->getActiveJobListings();
        $this->assertSame($jobs, $result);
    }

    public function testGetEmployerJobListings(): void
    {
        $jobs = [new JobPosting()];
        $this->jobRepoMock->expects($this->once())->method('findByEmployer')->with('emp1')->willReturn($jobs);
        $result = $this->service->getEmployerJobListings('emp1');
        $this->assertSame($jobs, $result);
    }

    public function testUpdateJobListingSuccess(): void
    {
        $job = new JobPosting();
        $job->setEmployerId('emp1');
        $job->setTitle('Old Title');

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->updateJobListing(1, ['title' => 'New Title'], 'emp1');

        $this->assertSame($job, $result);
        $this->assertSame('New Title', $result->getTitle());
    }

    public function testUpdateJobListingNotFound(): void
    {
        $this->jobRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);
        $result = $this->service->updateJobListing(999, [], 'emp1');
        $this->assertNull($result);
    }

    public function testUpdateJobListingUnauthorized(): void
    {
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized: not the owner');
        $this->service->updateJobListing(1, [], 'emp2');
    }

    public function testDeleteJobListingSuccess(): void
    {
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->emMock->expects($this->once())->method('remove')->with($job);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->deleteJobListing(1, 'emp1');
        $this->assertTrue($result);
    }

    public function testDeleteJobListingNotFound(): void
    {
        $this->jobRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);
        $result = $this->service->deleteJobListing(999, 'emp1');
        $this->assertFalse($result);
    }

    public function testDeleteJobListingUnauthorized(): void
    {
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized: not the owner');
        $this->service->deleteJobListing(1, 'emp2');
    }

    public function testSearchJobs(): void
    {
        $results = [new JobPosting()];
        $this->jobRepoMock->expects($this->once())->method('search')->with('dev', 'Hanoi', 'full-time', 'entry')->willReturn($results);
        $result = $this->service->searchJobs('dev', 'Hanoi', 'full-time', 'entry');
        $this->assertSame($results, $result);
    }

    public function testIncrementViewCount(): void
    {
        $this->jobRepoMock->expects($this->once())->method('incrementViewCount')->with(123);
        $this->service->incrementViewCount(123);
    }

    public function testIncrementApplicationCount(): void
    {
        $this->jobRepoMock->expects($this->once())->method('incrementApplicationCount')->with(123);
        $this->service->incrementApplicationCount(123);
    }

    public function testApplyForJobSuccess(): void
    {
        $job = new JobPosting();
        $job->setStatus('active');
        $job->setApplicationDeadline(new \DateTime('+1 week'));

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $existingApps = [];
        $this->applicationRepoMock->expects($this->once())->method('findByJob')->with(1)->willReturn($existingApps);
        $this->emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(JobApplication::class));
        $this->emMock->expects($this->once())->method('flush');
        $this->jobRepoMock->expects($this->once())->method('incrementApplicationCount')->with(1);

        $result = $this->service->applyForJob(1, 1001, 'Cover letter', []);
        $this->assertInstanceOf(JobApplication::class, $result);
        $this->assertSame(1001, $result->getApplicantId());
    }

    public function testApplyForJobNotFound(): void
    {
        $this->jobRepoMock->expects($this->once())->method('find')->with(9)->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Job not found');
        $this->service->applyForJob(9, 1001);
    }

    public function testApplyForJobInactive(): void
    {
        $job = new JobPosting();
        $job->setStatus('closed'); // use a non-active status
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot apply to inactive job');
        $this->service->applyForJob(1, 1001);
    }

    public function testApplyForJobDeadlinePassed(): void
    {
        $job = new JobPosting();
        $job->setStatus('active');
        // Set application deadline in the past via reflection (bypass validation)
        $reflection = new \ReflectionClass($job);
        $prop = $reflection->getProperty('applicationDeadline');
        $prop->setAccessible(true);
        $prop->setValue($job, new \DateTimeImmutable('-1 day'));

        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Application deadline has passed');
        $this->service->applyForJob(1, 1001);
    }

    public function testApplyForJobDuplicate(): void
    {
        $job = new JobPosting();
        $job->setStatus('active');
        $job->setApplicationDeadline(new \DateTime('+1 week'));

        $existingApp = new JobApplication(1, 1001);
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->applicationRepoMock->expects($this->once())->method('findByJob')->with(1)->willReturn([$existingApp]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You have already applied');
        $this->service->applyForJob(1, 1001);
    }

    public function testGetJobApplicationFound(): void
    {
        $app = new JobApplication(1, 1001);
        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $result = $this->service->getJobApplication(5);
        $this->assertSame($app, $result);
    }

    public function testGetJobApplicationNotFound(): void
    {
        $this->applicationRepoMock->expects($this->once())->method('find')->with(999)->willReturn(null);
        $result = $this->service->getJobApplication(999);
        $this->assertNull($result);
    }

    public function testGetJobApplications(): void
    {
        $apps = [new JobApplication(1, 1001), new JobApplication(1, 1002)];
        $this->applicationRepoMock->expects($this->once())->method('findByJob')->with(1)->willReturn($apps);
        $result = $this->service->getJobApplications(1);
        $this->assertSame($apps, $result);
    }

    public function testGetApplicantApplications(): void
    {
        $apps = [new JobApplication(1, 1001)];
        $this->applicationRepoMock->expects($this->once())->method('findByApplicant')->with(1001)->willReturn($apps);
        $result = $this->service->getApplicantApplications(1001);
        $this->assertSame($apps, $result);
    }

    public function testUpdateApplicationStatusSuccessAsApplicant(): void
    {
        $app = new JobApplication(1, 1001);
        $app->setStatus('pending');
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->updateApplicationStatus(5, 1001, 'accepted');
        $this->assertSame($app, $result);
        $this->assertSame('accepted', $result->getStatus());
    }

    public function testUpdateApplicationStatusSuccessAsEmployer(): void
    {
        $app = new JobApplication(1, 1001);
        $app->setStatus('pending');
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->updateApplicationStatus(5, 'emp1', 'rejected');
        $this->assertSame($app, $result);
        $this->assertSame('rejected', $result->getStatus());
    }

    public function testUpdateApplicationStatusUnauthorized(): void
    {
        $app = new JobApplication(1, 1001);
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->service->updateApplicationStatus(5, 'otheruser', 'accepted');
    }

    public function testAddApplicationNotesSuccess(): void
    {
        $app = new JobApplication(1, 1001);
        $job = new JobPosting();
        $job->setEmployerId('emp1');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $this->jobRepoMock->expects($this->once())->method('find')->with(1)->willReturn($job);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->addApplicationNotes(5, 1001, 'Notes');
        $this->assertSame($app, $result);
        // Assuming setNotes method exists
        $this->assertSame('Notes', $app->getNotes());
    }

    public function testWithdrawApplicationSuccess(): void
    {
        $app = new JobApplication(1, 1001);
        $app->setStatus('pending');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);
        $this->emMock->expects($this->once())->method('flush');

        $result = $this->service->withdrawApplication(5, 1001);
        $this->assertTrue($result);
        $this->assertSame('withdrawn', $app->getStatus());
    }

    public function testWithdrawApplicationAlreadyWithdrawn(): void
    {
        $app = new JobApplication(1, 1001);
        $app->setStatus('withdrawn');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);

        $result = $this->service->withdrawApplication(5, 1001);
        $this->assertTrue($result);
    }

    public function testWithdrawApplicationUnauthorized(): void
    {
        $app = new JobApplication(1, 1001);
        $app->setStatus('pending');

        $this->applicationRepoMock->expects($this->once())->method('find')->with(5)->willReturn($app);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->service->withdrawApplication(5, 'otheruser');
    }
}
