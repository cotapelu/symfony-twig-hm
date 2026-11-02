<?php

declare(strict_types=1);

namespace Tests\Unit\JobFinding\Controller;

use App\JobFinding\Controller\JobFindingController;
use App\JobFinding\Service\JobFindingService;
use App\JobFinding\Entity\JobPosting;
use App\JobFinding\Entity\JobApplication;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class JobFindingControllerTest extends TestCase
{
    private JobFindingService $serviceMock;
    private JobFindingController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(JobFindingService::class);
        $this->controller = new JobFindingController($this->serviceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(int $userId = 123): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn((string)$userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createJobMock(int $id, string $employerId = '123'): JobPosting
    {
        $job = $this->createMock(JobPosting::class);
        $job->method('getId')->willReturn($id);
        $job->method('getEmployerId')->willReturn($employerId);
        $job->method('getTitle')->willReturn('Engineer');
        $job->method('getDescription')->willReturn('Build things');
        $job->method('getLocation')->willReturn('Hanoi');
        $job->method('getJobType')->willReturn('full-time');
        $job->method('getExperienceLevel')->willReturn('mid');
        $job->method('getSalaryMin')->willReturn(1000);
        $job->method('getSalaryMax')->willReturn(2000);
        $job->method('getCurrency')->willReturn('USD');
        $job->method('getRequiredSkills')->willReturn([]);
        $job->method('getBenefits')->willReturn([]);
        $deadline = new \DateTimeImmutable('2025-01-01');
        $job->method('getApplicationDeadline')->willReturn($deadline);
        $job->method('getStatus')->willReturn('active');
        $job->method('getViewCount')->willReturn(0);
        $job->method('getApplicationCount')->willReturn(0);
        $now = new \DateTimeImmutable();
        $job->method('getCreatedAt')->willReturn($now);
        $job->method('getUpdatedAt')->willReturn($now);
        return $job;
    }

    private function createApplicationMock(int $id, int $jobId, int $applicantId, string $status = 'pending'): JobApplication
    {
        $app = $this->createMock(JobApplication::class);
        $app->method('getId')->willReturn($id);
        $app->method('getJobId')->willReturn($jobId);
        $app->method('getApplicantId')->willReturn($applicantId);
        $app->method('getStatus')->willReturn($status);
        $app->method('getCoverLetter')->willReturn('CL');
        $app->method('getDocuments')->willReturn(['doc.pdf']);
        $app->method('getNotes')->willReturn('Notes');
        $appliedAt = new \DateTimeImmutable();
        $app->method('getAppliedAt')->willReturn($appliedAt);
        $app->method('getUpdatedAt')->willReturn(null);
        return $app;
    }

    // ==================== sanitizeString ====================

    public function testSanitizeString_NullEmpty(): void
    {
        $this->assertSame('', $this->invokePrivate($this->controller, 'sanitizeString', [null]));
    }

    public function testSanitizeString_TrimsAndEscapes(): void
    {
        $input = '  <script>alert("x")</script>  ';
        $result = $this->invokePrivate($this->controller, 'sanitizeString', [$input]);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $result);
    }

    // ==================== Validation Helpers ====================

    public function testValidateJobCreationData_MissingTitle_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'title' is required");
        $this->invokePrivate($this->controller, 'validateJobCreationData', [['description' => 'D', 'location' => 'L', 'application_deadline' => '2025-01-01']]);
    }

    public function testValidateJobCreationData_MissingDescription_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'description' is required");
        $this->invokePrivate($this->controller, 'validateJobCreationData', [['title' => 'T', 'location' => 'L', 'application_deadline' => '2025-01-01']]);
    }

    public function testValidateJobCreationData_MissingLocation_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'location' is required");
        $this->invokePrivate($this->controller, 'validateJobCreationData', [['title' => 'T', 'description' => 'D', 'application_deadline' => '2025-01-01']]);
    }

    public function testValidateJobCreationData_MissingApplicationDeadline_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'application_deadline' is required");
        $this->invokePrivate($this->controller, 'validateJobCreationData', [['title' => 'T', 'description' => 'D', 'location' => 'L']]);
    }

    public function testValidateJobCreationData_Valid_NoException(): void
    {
        $data = [
            'title' => 'Software Engineer',
            'description' => 'Develop apps',
            'location' => 'Hanoi',
            'application_deadline' => '2025-01-01'
        ];
        $this->invokePrivate($this->controller, 'validateJobCreationData', [$data]);
        $this->assertTrue(true);
    }

    public function testValidateApplicationData_MissingJobId_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Job ID is required');
        $this->invokePrivate($this->controller, 'validateApplicationData', [['documents' => []]]);
    }

    public function testValidateApplicationData_InvalidDocuments_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Documents must be an array');
        $this->invokePrivate($this->controller, 'validateApplicationData', [['job_id' => 1, 'documents' => 'not array']]);
    }

    public function testValidateApplicationData_Valid_NoException(): void
    {
        $data = ['job_id' => 1, 'documents' => ['doc1.pdf']];
        $this->invokePrivate($this->controller, 'validateApplicationData', [$data]);
        $this->assertTrue(true);
    }

    public function testValidateOwnership_NotOwner_Throws(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: not the owner of this resource');
        $this->invokePrivate($this->controller, 'validateOwnership', ['employer1', 'user2']);
    }

    public function testValidateOwnership_Owner_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateOwnership', ['user123', 'user123']);
        $this->assertTrue(true);
    }

    // ==================== createJob ====================

    public function testCreateJob_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/job-finding/jobs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Engineer', 'description' => 'Build things', 'location' => 'Hanoi', 'application_deadline' => '2025-01-01'
        ]));
        $response = $this->controller->createJob($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateJob_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $data = ['title' => '', 'description' => 'D', 'location' => 'L', 'application_deadline' => '2025-01-01'];
        $request = Request::create('/api/job-finding/jobs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createJob($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testCreateJob_Success_Returns201(): void
    {
        $job = $this->createJobMock(1, '123');
        $this->serviceMock->expects($this->once())
            ->method('createJobListing')
            ->with(
                $this->callback(function($data) {
                    return $data['title'] === 'Engineer'
                        && $data['location'] === 'Hanoi'
                        && $data['application_deadline'] === '2025-01-01';
                }),
                '123'
            )
            ->willReturn($job);

        $this->setAuthenticatedUser(123);
        $data = [
            'title' => 'Engineer',
            'description' => 'Build things',
            'location' => 'Hanoi',
            'application_deadline' => '2025-01-01'
        ];
        $request = Request::create('/api/job-finding/jobs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createJob($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertSame(1, $content['data']['id']);
    }

    // ==================== getJob ====================

    public function testGetJob_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->method('getJobListing')->with(999)->willReturn(null);
        $response = $this->controller->getJob(999);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetJob_Found_Returns200(): void
    {
        $job = $this->createJobMock(100, '1');
        $this->serviceMock->method('getJobListing')->with(100)->willReturn($job);
        $this->setAuthenticatedUser();
        $response = $this->controller->getJob(100);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    // ==================== getActiveJobs ====================

    public function testGetActiveJobs_ReturnsList(): void
    {
        $job = $this->createJobMock(1, '123');
        $this->serviceMock->expects($this->once())
            ->method('getActiveJobListings')
            ->willReturn([$job]);
        $this->setAuthenticatedUser();
        $response = $this->controller->getActiveJobs();
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(1, $content['data']);
    }

    // ==================== applyForJob ====================

    public function testApplyForJob_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/job-finding/applications', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['job_id' => 1]));
        $response = $this->controller->applyForJob($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testApplyForJob_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $data = ['job_id' => '']; // empty job_id fails
        $request = Request::create('/api/job-finding/applications', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->applyForJob($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testApplyForJob_Success_Returns201(): void
    {
        $application = $this->createMock(\App\JobFinding\Entity\JobApplication::class);
        $application->method('getId')->willReturn(1);
        $application->method('getJobId')->willReturn(1);
        $application->method('getApplicantId')->willReturn(123);
        $application->method('getStatus')->willReturn('pending');
        $application->method('getCoverLetter')->willReturn(null);
        $application->method('getDocuments')->willReturn([]);
        $application->method('getNotes')->willReturn(null);
        $appliedAt = new \DateTimeImmutable();
        $application->method('getAppliedAt')->willReturn($appliedAt);
        $application->method('getUpdatedAt')->willReturn(null);

        $this->serviceMock->expects($this->once())
            ->method('applyForJob')
            ->with(1, 123, null, [])
            ->willReturn($application);
        $this->setAuthenticatedUser(123);
        $data = ['job_id' => 1];
        $request = Request::create('/api/job-finding/applications', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->applyForJob($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getMyApplications ====================

    public function testGetMyApplications_RequiresAuth(): void
    {
        $this->setAuthenticatedUser(123);
        $this->serviceMock->expects($this->once())
            ->method('getApplicantApplications')
            ->with(123)
            ->willReturn([]);
        $response = $this->controller->getMyApplications();
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getJobs & getEmployerJobs ====================

    public function testGetJobs_RequiresAuth(): void
    {
        $this->setAuthenticatedUser(123);
        $jobs = [$this->createJobMock(1, '123')];
        $this->serviceMock->expects($this->once())
            ->method('getEmployerJobListings')
            ->with('123')
            ->willReturn($jobs);
        $request = Request::create('/api/job-finding/jobs', 'GET');
        $response = $this->controller->getJobs($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(1, $data['data'][0]['id']);
    }

        public function testGetEmployerJobs_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $response = $this->controller->getEmployerJobs('123');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetEmployerJobs_OwnershipMismatch_Returns403(): void
    {
        $this->setAuthenticatedUser(123);
        $response = $this->controller->getEmployerJobs('456');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetEmployerJobs_Success(): void
    {
        $this->setAuthenticatedUser(123);
        $jobs = [$this->createJobMock(1, '123')];
        $this->serviceMock->expects($this->once())
            ->method('getEmployerJobListings')
            ->with('123')
            ->willReturn($jobs);
        $response = $this->controller->getEmployerJobs('123');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['data']);
    }
}