<?php

namespace App\JobFinding\Service;

use App\JobFinding\Entity\JobPosting;
use App\JobFinding\Entity\JobApplication;
use App\JobFinding\Repository\JobPostingRepository;
use App\JobFinding\Repository\JobApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class JobFindingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobPostingRepository $jobRepo,
        private JobApplicationRepository $applicationRepo
    ) {}

    // Job Listing Management

    public function createJobListing(array $data, string $employerId): JobPosting
    {
        $job = $this->buildJobPostingBase($data, $employerId);
        $this->setSalaryInfo($job, $data);
        $this->setOptionalFields($job, $data);
        $this->setApplicationDeadline($job, $data);
        $this->em->persist($job);
        $this->em->flush();
        return $job;
    }

    private function buildJobPostingBase(array $data, string $employerId): JobPosting
    {
        $job = new JobPosting();
        $job->setEmployerId($employerId);
        $job->setTitle($data['title'] ?? '');
        $job->setDescription($data['description'] ?? '');
        $job->setLocation($data['location'] ?? '');
        $job->setJobType($data['job_type'] ?? 'full-time');
        $job->setExperienceLevel($data['experience_level'] ?? 'entry');
        return $job;
    }

    private function setSalaryInfo(JobPosting $job, array $data): void
    {
        $min = $data['salary_min'] ?? null;
        $max = $data['salary_max'] ?? null;
        if ($min !== null) {
            $job->setSalaryMin((float)$min);
        }
        if ($max !== null) {
            $job->setSalaryMax((float)$max);
        }
    }

    private function setOptionalFields(JobPosting $job, array $data): void
    {
        if (isset($data['currency']) && $data['currency'] !== null) {
            $job->setCurrency($data['currency']);
        }
        if (isset($data['required_skills'])) {
            $job->setRequiredSkills($data['required_skills']);
        }
        if (isset($data['benefits'])) {
            $job->setBenefits($data['benefits']);
        }
    }

    private function setApplicationDeadline(JobPosting $job, array $data): void
    {
        if (empty($data['application_deadline'])) {
            throw new InvalidArgumentException('Application deadline is required');
        }
        $deadline = $data['application_deadline'];
        if (is_string($deadline)) {
            $deadline = new \DateTime($deadline);
        }
        $job->setApplicationDeadline($deadline);
    }

    public function getJobListing(int $id)
    {
        return $this->jobRepo->find($id);
    }

    public function getActiveJobListings()
    {
        return $this->jobRepo->findActive();
    }

    public function getEmployerJobListings(string $employerId)
    {
        return $this->jobRepo->findByEmployer($employerId);
    }

    public function updateJobListing(int $id, array $data, string $employerId): ?JobPosting
    {
        $job = $this->jobRepo->find($id);
        if (!$job) {
            return null;
        }
        $this->ensureJobOwnership($job, $employerId);
        $this->applyJobUpdates($job, $data);
        $this->em->flush();
        return $job;
    }

    private function ensureJobOwnership(JobPosting $job, string $employerId): void
    {
        if ($job->getEmployerId() !== $employerId) {
            throw new InvalidArgumentException('Unauthorized: not the owner of this job');
        }
    }

    private function applyJobUpdates(JobPosting $job, array $data): void
    {
        $this->updateTitleIfPresent($job, $data);
        $this->updateDescriptionIfPresent($job, $data);
        $this->updateLocationIfPresent($job, $data);
        $this->updateJobTypeIfPresent($job, $data);
        $this->updateExperienceLevelIfPresent($job, $data);
        $this->updateSalaryMinIfPresent($job, $data);
        $this->updateSalaryMaxIfPresent($job, $data);
        $this->updateCurrencyIfPresent($job, $data);
        $this->updateRequiredSkillsIfPresent($job, $data);
        $this->updateBenefitsIfPresent($job, $data);
        $this->updateApplicationDeadlineIfPresent($job, $data);
        $this->updateStatusIfPresent($job, $data);
    }

    private function updateTitleIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('title', $data)) {
            $job->setTitle($data['title']);
        }
    }

    private function updateDescriptionIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('description', $data)) {
            $job->setDescription($data['description']);
        }
    }

    private function updateLocationIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('location', $data)) {
            $job->setLocation($data['location']);
        }
    }

    private function updateJobTypeIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('job_type', $data)) {
            $job->setJobType($data['job_type']);
        }
    }

    private function updateExperienceLevelIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('experience_level', $data)) {
            $job->setExperienceLevel($data['experience_level']);
        }
    }

    private function updateSalaryMinIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('salary_min', $data)) {
            $job->setSalaryMin($data['salary_min'] !== null ? (float)$data['salary_min'] : null);
        }
    }

    private function updateSalaryMaxIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('salary_max', $data)) {
            $job->setSalaryMax($data['salary_max'] !== null ? (float)$data['salary_max'] : null);
        }
    }

    private function updateCurrencyIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('currency', $data)) {
            $job->setCurrency($data['currency']);
        }
    }

    private function updateRequiredSkillsIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('required_skills', $data)) {
            $job->setRequiredSkills($data['required_skills']);
        }
    }

    private function updateBenefitsIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('benefits', $data)) {
            $job->setBenefits($data['benefits']);
        }
    }

    private function updateApplicationDeadlineIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('application_deadline', $data) && $data['application_deadline'] !== null) {
            $deadline = $data['application_deadline'];
            if (is_string($deadline)) {
                $deadline = new \DateTime($deadline);
            }
            $job->setApplicationDeadline($deadline);
        }
    }

    private function updateStatusIfPresent(JobPosting $job, array $data): void
    {
        if (array_key_exists('status', $data)) {
            $job->setStatus($data['status']);
        }
    }

    public function deleteJobListing(int $id, string $employerId)
    {
        $job = $this->jobRepo->find($id);
        if (!$job) {
            return false;
        }

        if ($job->getEmployerId() !== $employerId) {
            throw new InvalidArgumentException('Unauthorized: not the owner of this job');
        }

        $this->em->remove($job);
        $this->em->flush();

        return true;
    }

    public function searchJobs(string $query, ?string $location = null, ?string $jobType = null, ?string $experienceLevel = null)
    {
        return $this->jobRepo->search($query, $location, $jobType, $experienceLevel);
    }

    // Atomic counter operations

    public function incrementViewCount(int $jobId)
    {
        $this->jobRepo->incrementViewCount($jobId);
    }

    public function incrementApplicationCount(int $jobId)
    {
        $this->jobRepo->incrementApplicationCount($jobId);
    }

    // Job Application Management

    public function applyForJob(int $jobId, int $applicantId, ?string $coverLetter = null, array $documents = [])
    {
        $job = $this->jobRepo->find($jobId);
        if (!$job) {
            throw new InvalidArgumentException('Job not found');
        }

        // Check if job is active
        if ($job->getStatus() !== 'active') {
            throw new InvalidArgumentException('Cannot apply to inactive job');
        }

        // Check if deadline has passed
        $deadline = $job->getApplicationDeadline();
        if ($deadline < new \DateTime()) {
            throw new InvalidArgumentException('Application deadline has passed');
        }

        // Check for duplicate application (applicant already applied)
        $existing = $this->applicationRepo->findByJob($jobId);
        foreach ($existing as $app) {
            if ($app->getApplicantId() === $applicantId && $app->getStatus() !== 'withdrawn') {
                throw new InvalidArgumentException('You have already applied to this job');
            }
        }

        $application = new JobApplication($jobId, $applicantId, $coverLetter);
        if (!empty($documents)) {
            $application->setDocuments($documents);
        }

        $this->em->persist($application);
        $this->em->flush();

        // Increment application count atomically
        $this->incrementApplicationCount($jobId);

        return $application;
    }

    public function getJobApplication(int $id)
    {
        return $this->applicationRepo->find($id);
    }

    public function getJobApplications(int $jobId)
    {
        return $this->applicationRepo->findByJob($jobId);
    }

    public function getApplicantApplications(int $applicantId)
    {
        return $this->applicationRepo->findByApplicant($applicantId);
    }

    public function updateApplicationStatus(int $id, mixed $userId, string $status)
    {
        $application = $this->applicationRepo->find($id);
        if (!$application) {
            return null;
        }

        // Authorization: applicant can update their own, or employer of the job
        $job = $this->jobRepo->find($application->getJobId());
        if (!$job) {
            throw new InvalidArgumentException('Associated job not found');
        }

        $isApplicant = $application->getApplicantId() === $userId;
        $isEmployer = (string) $userId === $job->getEmployerId();

        if (!$isApplicant && !$isEmployer) {
            throw new InvalidArgumentException('Unauthorized: can only update your own application or jobs you own');
        }

        $application->setStatus($status);
        $this->em->flush();

        return $application;
    }

    public function addApplicationNotes(int $id, mixed $userId, string $notes)
    {
        $application = $this->applicationRepo->find($id);
        if (!$application) {
            return null;
        }

        // Authorization: only applicant can add notes, or employer
        $job = $this->jobRepo->find($application->getJobId());
        if (!$job) {
            throw new InvalidArgumentException('Associated job not found');
        }

        $isApplicant = $application->getApplicantId() === $userId;
        $isEmployer = (string) $userId === $job->getEmployerId();

        if (!$isApplicant && !$isEmployer) {
            throw new InvalidArgumentException('Unauthorized: can only add notes to your own application or jobs you own');
        }

        $application->setNotes($notes);
        $this->em->flush();

        return $application;
    }

    public function withdrawApplication(int $id, mixed $userId)
    {
        $application = $this->applicationRepo->find($id);
        if (!$application) {
            return false;
        }

        // Only applicant can withdraw their own application
        if ($application->getApplicantId() !== $userId) {
            throw new InvalidArgumentException('Unauthorized: can only withdraw your own application');
        }

        if ($application->getStatus() === 'withdrawn') {
            return true; // already withdrawn
        }

        $application->setStatus('withdrawn');
        $this->em->flush();

        return true;
    }
}
