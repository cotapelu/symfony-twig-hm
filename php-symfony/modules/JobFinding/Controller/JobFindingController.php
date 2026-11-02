<?php

declare(strict_types=1);

namespace App\JobFinding\Controller;

use App\JobFinding\Service\JobFindingService;
use App\Shared\Controller\BaseController;
use App\JobFinding\Entity\JobPosting;
use App\JobFinding\Entity\JobApplication;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/job-finding')]
class JobFindingController extends BaseController
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    public function __construct(private readonly JobFindingService $service) {}

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleException('job_finding', $e);
        }
    }

    // ==================== Job Listings ====================

    #[Route('/jobs', name: 'job_finding_create_job', methods: ['POST'])]
    public function createJob(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createJobAction($request));
    }

    private function createJobAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        $this->validateJobCreationData($data);
        $data['title'] = $this->sanitizeString($data['title']);
        $data['description'] = $this->sanitizeString($data['description']);
        $data['location'] = $this->sanitizeString($data['location']);
        $job = $this->service->createJobListing($data, $userId);
        $this->logAction('job_created', ['job_id' => $job->getId(), 'employer_id' => $userId]);
        $this->incrementMetric('jobs_created_total', ['employer_id' => $userId]);
        return $this->json(['data' => $this->serializeJob($job)], Response::HTTP_CREATED);
    }

    #[Route('/jobs', name: 'job_finding_get_jobs', methods: ['GET'])]
    public function getJobs(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getJobsAction($request));
    }

    private function getJobsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $jobs = $this->service->getEmployerJobListings($userId);
        $this->logAction('jobs_listed', ['employer_id' => $userId]);
        $this->incrementMetric('jobs_listed_total', ['employer_id' => $userId]);
        return $this->json(['data' => array_map([$this, 'serializeJob'], $jobs)]);
    }

    #[Route('/jobs/active', name: 'job_finding_get_active_jobs', methods: ['GET'])]
    public function getActiveJobs(): JsonResponse
    {
        return $this->handle(fn() => $this->getActiveJobsAction());
    }

    private function getActiveJobsAction(): JsonResponse
    {
        $jobs = $this->service->getActiveJobListings();
        $this->incrementMetric('active_jobs_queried');
        return $this->json(['data' => array_map([$this, 'serializeJob'], $jobs)]);
    }

    #[Route('/jobs/search', name: 'job_finding_search_jobs', methods: ['GET'])]
    public function searchJobs(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->searchJobsAction($request));
    }

    private function searchJobsAction(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        if (empty($query)) throw new BadRequestHttpException('Search query "q" is required');
        $location = $request->query->get('location');
        $jobType = $request->query->get('job_type');
        $experienceLevel = $request->query->get('experience_level');
        $jobs = $this->service->searchJobs($query, $location, $jobType, $experienceLevel);
        $this->logAction('jobs_searched', ['query' => $query, 'filters' => compact('location','jobType','experienceLevel')]);
        $this->incrementMetric('jobs_searched_total');
        return $this->json(['data' => array_map([$this, 'serializeJob'], $jobs)]);
    }

    #[Route('/jobs/{id}', name: 'job_finding_get_job', methods: ['GET'])]
    public function getJob(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getJobAction($id));
    }

    private function getJobAction(int $id): JsonResponse
    {
        $job = $this->service->getJobListing($id);
        if (!$job) throw new NotFoundHttpException('Job not found');
        try {
            $this->service->incrementViewCount($id);
        } catch (\Exception $e) {
            $this->logAction('view_count_increment_failed', ['job_id' => $id, 'error' => $e->getMessage()]);
        }
        $this->logAction('job_viewed', ['job_id' => $id]);
        $this->incrementMetric('job_views_total', ['job_id' => $id]);
        return $this->json(['data' => $this->serializeJob($job)]);
    }

    #[Route('/jobs/{id}', name: 'job_finding_update_job', methods: ['PUT'])]
    public function updateJob(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateJobAction($id, $request));
    }

    private function updateJobAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        $this->sanitizeJobUpdateData($data);
        $job = $this->service->updateJobListing($id, $data, $userId);
        if (!$job) throw new NotFoundHttpException('Job not found');
        $this->logAction('job_updated', ['job_id' => $id, 'employer_id' => $userId]);
        $this->incrementMetric('jobs_updated_total');
        return $this->json(['data' => $this->serializeJob($job)]);
    }

    #[Route('/jobs/{id}', name: 'job_finding_delete_job', methods: ['DELETE'])]
    public function deleteJob(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteJobAction($id, $request));
    }

    private function deleteJobAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $deleted = $this->service->deleteJobListing($id, $userId);
        if (!$deleted) throw new NotFoundHttpException('Job not found');
        $this->logAction('job_deleted', ['job_id' => $id, 'employer_id' => $userId]);
        $this->incrementMetric('jobs_deleted_total');
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/employer/{employerId}/jobs', name: 'job_finding_get_employer_jobs', methods: ['GET'])]
    public function getEmployerJobs(string $employerId): JsonResponse
    {
        return $this->handle(fn() => $this->getEmployerJobsAction($employerId));
    }

    private function getEmployerJobsAction(string $employerId): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $this->validateOwnership($employerId, $userId);
        $jobs = $this->service->getEmployerJobListings($employerId);
        $this->logAction('employer_jobs_retrieved', ['employer_id' => $employerId]);
        $this->incrementMetric('employer_jobs_retrieved_total');
        return $this->json(['data' => array_map([$this, 'serializeJob'], $jobs)]);
    }

    // ==================== Job Applications ====================

    #[Route('/applications', name: 'job_finding_apply_job', methods: ['POST'])]
    public function applyForJob(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->applyForJobAction($request));
    }

    private function applyForJobAction(Request $request): JsonResponse
    {
        $userId = (int) $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        $this->validateApplicationData($data);
        $jobId = (int) $data['job_id'];
        $coverLetter = isset($data['cover_letter']) ? $this->sanitizeString($data['cover_letter']) : null;
        $documents = isset($data['documents']) ? array_map(fn($doc) => filter_var(trim($doc), FILTER_SANITIZE_URL), (array)$data['documents']) : [];
        $application = $this->service->applyForJob($jobId, $userId, $coverLetter, $documents);
        $this->logAction('job_applied', ['job_id' => $jobId, 'applicant_id' => $userId, 'application_id' => $application->getId()]);
        $this->incrementMetric('job_applications_total', ['job_id' => $jobId]);
        return $this->json(['data' => $this->serializeApplication($application)], Response::HTTP_CREATED);
    }

    #[Route('/applications', name: 'job_finding_get_my_applications', methods: ['GET'])]
    public function getMyApplications(): JsonResponse
    {
        return $this->handle(fn() => $this->getMyApplicationsAction());
    }

    private function getMyApplicationsAction(): JsonResponse
    {
        $userId = (int) $this->requireAuthOr401AsString();
        $applications = $this->service->getApplicantApplications($userId);
        $this->logAction('my_applications_retrieved', ['user_id' => $userId]);
        return $this->json(['data' => array_map([$this, 'serializeApplication'], $applications)]);
    }

    #[Route('/jobs/{jobId}/applications', name: 'job_finding_get_job_applications', methods: ['GET'])]
    public function getJobApplications(int $jobId): JsonResponse
    {
        return $this->handle(fn() => $this->getJobApplicationsAction($jobId));
    }

    private function getJobApplicationsAction(int $jobId): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $job = $this->service->getJobListing($jobId);
        if (!$job) throw new NotFoundHttpException('Job not found');
        if ($job->getEmployerId() !== $userId) throw new AccessDeniedHttpException('Access denied: you are not the employer for this job');
        $applications = $this->service->getJobApplications($jobId);
        $this->logAction('job_applications_retrieved', ['job_id' => $jobId, 'employer_id' => $userId]);
        return $this->json(['data' => array_map([$this, 'serializeApplication'], $applications)]);
    }

    #[Route('/applications/{id}', name: 'job_finding_get_application', methods: ['GET'])]
    public function getApplication(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getApplicationAction($id));
    }

    private function getApplicationAction(int $id): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $application = $this->service->getJobApplication($id);
        if (!$application) throw new NotFoundHttpException('Application not found');
        $job = $this->service->getJobListing($application->getJobId());
        if (!$job) throw new NotFoundHttpException('Associated job not found');
        if ($application->getApplicantId() !== $userId && $job->getEmployerId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied to this application');
        }
        $this->logAction('application_viewed', ['application_id' => $id, 'user_id' => $userId]);
        return $this->json(['data' => $this->serializeApplication($application)]);
    }

    #[Route('/applications/{id}/status', name: 'job_finding_update_application_status', methods: ['PUT'])]
    public function updateApplicationStatus(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateApplicationStatusAction($id, $request));
    }

    private function updateApplicationStatusAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data['status'])) throw new InvalidArgumentException('Status is required');
        $status = $this->sanitizeString($data['status']);
        $application = $this->service->updateApplicationStatus($id, $userId, $status);
        if (!$application) throw new NotFoundHttpException('Application not found');
        $this->logAction('application_status_updated', ['application_id' => $id, 'user_id' => $userId, 'new_status' => $status]);
        $this->incrementMetric('application_status_updates_total');
        return $this->json(['data' => $this->serializeApplication($application)]);
    }

    #[Route('/applications/{id}/notes', name: 'job_finding_add_application_notes', methods: ['PUT'])]
    public function addApplicationNotes(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->addApplicationNotesAction($id, $request));
    }

    private function addApplicationNotesAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $data = $this->getJsonData($request);
        if (empty($data['notes'])) throw new InvalidArgumentException('Notes are required');
        $notes = $this->sanitizeString($data['notes']);
        $application = $this->service->addApplicationNotes($id, $userId, $notes);
        if (!$application) throw new NotFoundHttpException('Application not found');
        $this->logAction('application_notes_added', ['application_id' => $id, 'user_id' => $userId]);
        return $this->json(['data' => $this->serializeApplication($application)]);
    }

    #[Route('/applications/{id}/withdraw', name: 'job_finding_withdraw_application', methods: ['DELETE'])]
    public function withdrawApplication(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->withdrawApplicationAction($id, $request));
    }

    private function withdrawApplicationAction(int $id, Request $request): JsonResponse
    {
        $userId = $this->requireAuthOr401AsString();
        $withdrawn = $this->service->withdrawApplication($id, $userId);
        if (!$withdrawn) throw new NotFoundHttpException('Application not found');
        $this->logAction('application_withdrawn', ['application_id' => $id, 'user_id' => $userId]);
        $this->incrementMetric('applications_withdrawn_total');
        return $this->json(['data' => ['message' => 'Application withdrawn']]);
    }

    // ========== PRIVATE HELPERS ==========

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        return $user->getUserIdentifier();
    }

    private function requireAuthOr401AsString(): string
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) {
            throw new AccessDeniedHttpException('Authentication required');
        }
        return $userIdOr401;
    }

    private function validateOwnership(string $employerId, string $userId): void
    {
        if ($employerId !== $userId) {
            throw new AccessDeniedHttpException('Access denied: not the owner of this resource');
        }
    }

    private function sanitizeString(?string $value): string
    {
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : '';
    }

    private function sanitizeJobUpdateData(array &$data): void
    {
        if (isset($data['title'])) $data['title'] = $this->sanitizeString($data['title']);
        if (isset($data['description'])) $data['description'] = $this->sanitizeString($data['description']);
        if (isset($data['location'])) $data['location'] = $this->sanitizeString($data['location']);
    }

    private function validateJobCreationData(array $data): void
    {
        $required = ['title', 'description', 'location', 'application_deadline'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? null)) throw new InvalidArgumentException("Field '{$field}' is required");
        }
    }

    private function validateApplicationData(array $data): void
    {
        if (empty($data['job_id'])) throw new InvalidArgumentException('Job ID is required');
        if (isset($data['documents']) && !is_array($data['documents'])) {
            throw new InvalidArgumentException('Documents must be an array');
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("JobFindingController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? 'anonymous';
        $timestamp = new DateTimeImmutable();
        error_log(json_encode([
            'timestamp' => $timestamp->format(DateTimeImmutable::ATOM),
            'action' => $action,
            'userId' => $userId,
            'module' => 'JobFinding',
            'context' => $context
        ]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode([
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
        ]));
    }

    // Serialization helpers (unchanged)

    private function serializeJob(JobPosting $job): array
    {
        return [
            'id' => $job->getId(),
            'employer_id' => $job->getEmployerId(),
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
            'location' => $job->getLocation(),
            'job_type' => $job->getJobType(),
            'experience_level' => $job->getExperienceLevel(),
            'salary_min' => $job->getSalaryMin(),
            'salary_max' => $job->getSalaryMax(),
            'currency' => $job->getCurrency(),
            'required_skills' => $job->getRequiredSkills() ?? [],
            'benefits' => $job->getBenefits() ?? [],
            'application_deadline' => $job->getApplicationDeadline()->format('Y-m-d'),
            'status' => $job->getStatus(),
            'view_count' => $job->getViewCount(),
            'application_count' => $job->getApplicationCount(),
            'created_at' => $job->getCreatedAt()->format(DateTimeImmutable::ATOM),
            'updated_at' => $job->getUpdatedAt()->format(DateTimeImmutable::ATOM)
        ];
    }

    private function serializeApplication(JobApplication $app): array
    {
        return [
            'id' => $app->getId(),
            'job_id' => $app->getJobId(),
            'applicant_id' => $app->getApplicantId(),
            'status' => $app->getStatus(),
            'cover_letter' => $app->getCoverLetter(),
            'documents' => $app->getDocuments() ?? [],
            'notes' => $app->getNotes(),
            'applied_at' => $app->getAppliedAt()->format(DateTimeImmutable::ATOM),
            'updated_at' => $app->getUpdatedAt()?->format(DateTimeImmutable::ATOM)
        ];
    }
}