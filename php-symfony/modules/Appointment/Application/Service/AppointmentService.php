<?php

declare(strict_types=1);

namespace App\Appointment\Application\Service;

use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Domain\Repository\AppointmentRepositoryInterface;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\OptimisticLockException;

class AppointmentService
{
    private AppointmentRepositoryInterface $repository;
    private LoggerInterface $logger;
    private ?string $userId = null;

    public function __construct(
        AppointmentRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function createAppointment(string $wardCode, string $citizenName, string $citizenPhone, string $appointmentDate, string $appointmentTime, string $purpose, ?string $citizenEmail = null, ?string $citizenCCCD = null, ?string $department = null, ?int $durationMinutes = null, ?string $notes = null, ?string $description = null): Appointment {
        $date = $this->validateAllAppointmentInputs(
            $wardCode,
            $citizenName,
            $citizenPhone,
            $appointmentDate,
            $appointmentTime,
            $purpose,
            $citizenEmail,
            $citizenCCCD,
            $department,
            $durationMinutes,
            $notes,
            $description
        );
        $appointmentId = $this->generateAppointmentId();
        $appointment = $this->buildAppointment($appointmentId, $wardCode, $citizenName, $citizenPhone, $date, $appointmentTime, $purpose, $citizenEmail, $citizenCCCD, $department, $durationMinutes, $notes, $description);
        $this->saveAppointment($appointment);
        $this->logAppointmentCreated($appointment);
        return $appointment;
    }

    private function validateAllAppointmentInputs(string $wardCode, string $citizenName, string $citizenPhone, string $appointmentDate, string $appointmentTime, string $purpose, ?string $citizenEmail, ?string $citizenCCCD, ?string $department, ?int $durationMinutes, ?string $notes, ?string $description): DateTimeImmutable {
        $this->validateAuthentication();
        $date = $this->validateAppointmentDate($appointmentDate);
        $this->validateWardCode($wardCode);
        $this->validateCitizenName($citizenName);
        $this->validateCitizenPhone($citizenPhone);
        $this->validateAppointmentTime($appointmentTime);
        $this->validatePurpose($purpose);
        $this->validateCitizenEmail($citizenEmail);
        $this->validateCitizenCCCD($citizenCCCD);
        $this->validateDepartment($department);
        $this->validateDurationMinutes($durationMinutes);
        $this->validateNotes($notes);
        $this->validateDescription($description);
        return $date;
    }

    private function generateAppointmentId(): string
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate UUID', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to generate appointment ID');
        }
    }

    private function buildAppointment(string $appointmentId, string $wardCode, string $citizenName, string $citizenPhone, DateTimeImmutable $date, string $appointmentTime, string $purpose, ?string $citizenEmail, ?string $citizenCCCD, ?string $department, ?int $durationMinutes, ?string $notes, ?string $description): Appointment {
        $appointment = new Appointment(
            $appointmentId,
            $wardCode,
            $citizenName,
            $citizenPhone,
            $date,
            $appointmentTime,
            $purpose
        );

        $appointment->setCitizenUserId($this->userId);
        $appointment->setCitizenEmail($citizenEmail);
        $appointment->setCitizenCCCD($citizenCCCD);
        $appointment->setDepartment($department);

        if ($durationMinutes !== null) {
            $appointment->setDurationMinutes($durationMinutes);
        }

        $appointment->setNotes($notes);
        $appointment->setDescription($description);

        return $appointment;
    }

    private function saveAppointment(Appointment $appointment): void
    {
        try {
            $this->repository->save($appointment);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save appointment', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to save appointment');
        }
    }

    private function logAppointmentCreated(Appointment $appointment): void
    {
        $this->logger->info('Appointment created successfully', [
            'appointmentId' => $appointment->getId(),
            'wardCode' => $appointment->getWardCode(),
            'citizenName' => $appointment->getCitizenName(),
            'userId' => $this->userId
        ]);
    }

    public function getAppointment(string $id): ?Appointment
    {
        try {
            $appointment = $this->repository->findById($id);
            if ($appointment === null) {
                $this->logger->warning('Appointment not found', ['appointmentId' => $id]);
                return null;
            }
            $this->logger->info('Appointment retrieved successfully', ['appointmentId' => $id]);
            return $appointment;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointment', ['appointmentId' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getAppointmentByNumber(string $number): ?Appointment
    {
        try {
            $appointment = $this->repository->findByNumber($number);
            if ($appointment === null) {
                $this->logger->warning('Appointment not found by number', ['appointmentNumber' => $number]);
                return null;
            }
            $this->logger->info('Appointment retrieved by number successfully', ['appointmentNumber' => $number, 'appointmentId' => $appointment->getId()]);
            return $appointment;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointment by number', ['appointmentNumber' => $number, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getAppointmentsByWard(string $wardCode, ?string $status = null, ?int $limit = null): array
    {
        try {
            $result = $this->repository->findByWard($wardCode, $status, $limit);
            $this->logger->info('Appointments retrieved by ward', [
                'wardCode' => $wardCode,
                'status' => $status,
                'limit' => $limit,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointments by ward', [
                'wardCode' => $wardCode,
                'status' => $status,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAppointmentsByOfficer(string $officerId, ?string $status = null): array
    {
        try {
            $result = $this->repository->findByOfficer($officerId, $status);
            $this->logger->info('Appointments retrieved by officer', [
                'officerId' => $officerId,
                'status' => $status,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointments by officer', [
                'officerId' => $officerId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAppointmentsByPhone(string $phone): array
    {
        try {
            $result = $this->repository->findByPhone($phone);
            $this->logger->info('Appointments retrieved by phone', [
                'phone' => $phone,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointments by phone', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAvailableSlots(string $wardCode, string $date): array
    {
        try {
            $result = $this->repository->findAvailableSlots($wardCode, $date);
            $this->logger->info('Available slots retrieved', [
                'wardCode' => $wardCode,
                'date' => $date,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve available slots', [
                'wardCode' => $wardCode,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getUpcomingAppointments(?string $officerId = null): array
    {
        try {
            $result = $this->repository->findUpcomingAppointments($officerId);
            $this->logger->info('Upcoming appointments retrieved', [
                'officerId' => $officerId,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve upcoming appointments', [
                'officerId' => $officerId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getTodayAppointments(string $wardCode, ?string $officerId = null): array
    {
        try {
            $result = $this->repository->findTodayAppointments($wardCode, $officerId);
            $this->logger->info('Today appointments retrieved', [
                'wardCode' => $wardCode,
                'officerId' => $officerId,
                'count' => count($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve today appointments', [
                'wardCode' => $wardCode,
                'officerId' => $officerId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function confirmAppointment(string $appointmentId): ?Appointment
    {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->confirm();
                $this->repository->save($appointment);
                $this->logger->info('Appointment confirmed successfully', ['appointmentId' => $appointmentId]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to confirm appointment', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function startAppointment(string $appointmentId): ?Appointment
    {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->start();
                $this->repository->save($appointment);
                $this->logger->info('Appointment started successfully', ['appointmentId' => $appointmentId]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to start appointment', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function completeAppointment(
        string $appointmentId,
        ?string $officerNotes = null,
        ?int $citizenRating = null,
        ?string $citizenFeedback = null
    ): ?Appointment {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId, $officerNotes, $citizenRating, $citizenFeedback) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->complete($officerNotes, $citizenRating, $citizenFeedback);
                $this->repository->save($appointment);
                $this->logger->info('Appointment completed successfully', [
                    'appointmentId' => $appointmentId,
                    'citizenRating' => $citizenRating
                ]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to complete appointment', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function cancelAppointment(string $appointmentId, string $reason, string $cancelBy): ?Appointment
    {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId, $reason, $cancelBy) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->cancel($reason, $cancelBy);
                $this->repository->save($appointment);
                $this->logger->info('Appointment cancelled successfully', [
                    'appointmentId' => $appointmentId,
                    'reason' => $reason,
                    'cancelBy' => $cancelBy
                ]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel appointment', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function markNoShow(string $appointmentId): ?Appointment
    {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->markNoShow();
                $this->repository->save($appointment);
                $this->logger->info('Appointment marked as no show successfully', ['appointmentId' => $appointmentId]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark appointment as no show', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function assignOfficer(string $appointmentId, string $officerId): ?Appointment
    {
        try {
            return $this->executeWithOptimisticRetry(function() use ($appointmentId, $officerId) {
                $appointment = $this->repository->findById($appointmentId);
                if ($appointment === null) {
                    $this->logger->warning('Appointment not found', ['appointmentId' => $appointmentId]);
                    return null;
                }
                $appointment->setOfficerId($officerId);
                $this->repository->save($appointment);
                $this->logger->info('Officer assigned to appointment successfully', [
                    'appointmentId' => $appointmentId,
                    'officerId' => $officerId
                ]);
                return $appointment;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to assign officer to appointment', ['appointmentId' => $appointmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

     public function getStatistics(string $wardCode, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): array
     {
         try {
             $result = $this->repository->getStatistics($wardCode, $startDate, $endDate);
             $this->logger->info('Statistics retrieved', [
                 'wardCode' => $wardCode,
                 'startDate' => $startDate ? $startDate->format('Y-m-d H:i:s') : null,
                 'endDate' => $endDate ? $endDate->format('Y-m-d H:i:s') : null
             ]);
             return $result;
         } catch (\Exception $e) {
             $this->logger->error('Failed to retrieve statistics', [
                 'wardCode' => $wardCode,
                 'startDate' => $startDate ? $startDate->format('Y-m-d H:i:s') : null,
                 'endDate' => $endDate ? $endDate->format('Y-m-d H:i:s') : null,
                 'error' => $e->getMessage()
             ]);
             return [];
         }
     }

     public function ensureCanAccessAppointment(Appointment $appointment, string $userId, string $role): void
     {
         // Admin can access all
         if (in_array('ROLE_ADMIN', explode(',', $role), true)) {
             return;
         }

         // Citizen can only access own appointments
         if (in_array('ROLE_CITIZEN', explode(',', $role), true)) {
             if ($appointment->getCitizenUserId() !== $userId) {
                 throw new \InvalidArgumentException('Forbidden: cannot access appointment');
             }
             return;
         }

         // Officer can access appointments assigned to them
         if (in_array('ROLE_OFFICER', explode(',', $role), true)) {
             if ($appointment->getOfficerId() === $userId) {
                 return;
             }
             throw new \InvalidArgumentException('Forbidden: cannot access appointment');
         }

         throw new \InvalidArgumentException('Forbidden');
     }

     public function isAppointmentOwnedByCitizen(Appointment $appointment, string $userId): bool
     {
         return $appointment->getCitizenUserId() === $userId;
     }

     public function isAppointmentOwnedByOfficer(Appointment $appointment, string $officerId): bool
     {
         return $appointment->getOfficerId() === $officerId;
     }

    /**
     * Executes an operation with optimistic locking retry.
     *
     * @template T
     * @param callable(): T $operation The operation to execute (should include find and save)
     * @param int $maxRetries Maximum number of retries (default 3)
     * @return T
     * @throws \RuntimeException If all retries fail due to concurrent updates
     */
    private function executeWithOptimisticRetry(callable $operation, int $maxRetries = 3)
    {
        $lastException = null;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (OptimisticLockException $e) {
                $lastException = $e;
                if ($attempt === $maxRetries - 1) {
                    break;
                }
                usleep(10000 * ($attempt + 1));
            }
        }
        throw new \RuntimeException('Operation failed due to concurrent updates. Please try again.', 0, $lastException);
    }

    private function validateAuthentication(): void
    {
        if ($this->userId === null) {
            $this->logger->warning('User not authenticated for appointment creation');
            throw new \RuntimeException('User not authenticated');
        }
    }

    private function validateWardCode(string $wardCode): void
    {
        if (empty(trim($wardCode))) {
            $this->logger->warning('Ward code is required');
            throw new \InvalidArgumentException('Ward code is required');
        }
        if (strlen($wardCode) > 20) {
            $this->logger->warning('Ward code exceeds 20 characters');
            throw new \InvalidArgumentException('Ward code cannot exceed 20 characters');
        }
    }

    private function validateCitizenName(string $citizenName): void
    {
        if (empty(trim($citizenName))) {
            $this->logger->warning('Citizen name is required');
            throw new \InvalidArgumentException('Citizen name is required');
        }
        if (strlen($citizenName) > 255) {
            $this->logger->warning('Citizen name exceeds 255 characters');
            throw new \InvalidArgumentException('Citizen name cannot exceed 255 characters');
        }
    }

    private function validateCitizenPhone(string $citizenPhone): void
    {
        if (empty(trim($citizenPhone))) {
            $this->logger->warning('Citizen phone is required');
            throw new \InvalidArgumentException('Citizen phone is required');
        }
        if (strlen($citizenPhone) > 20) {
            $this->logger->warning('Citizen phone exceeds 20 characters');
            throw new \InvalidArgumentException('Citizen phone cannot exceed 20 characters');
        }
        if (!preg_match('/^[\d\s\-\(\)\+]+$/', $citizenPhone)) {
            $this->logger->warning('Citizen phone contains invalid characters');
            throw new \InvalidArgumentException('Citizen phone contains invalid characters');
        }
    }

    private function validateAppointmentDate(string $appointmentDate): DateTimeImmutable
    {
        if (empty(trim($appointmentDate))) {
            $this->logger->warning('Appointment date is required');
            throw new \InvalidArgumentException('Appointment date is required');
        }
        try {
            return new DateTimeImmutable($appointmentDate);
        } catch (\Exception $e) {
            $this->logger->error('Invalid appointment date format', ['error' => $e->getMessage()]);
            throw new \InvalidArgumentException('Invalid appointment date format');
        }
    }

    private function validateAppointmentTime(string $appointmentTime): void
    {
        if (empty(trim($appointmentTime))) {
            $this->logger->warning('Appointment time is required');
            throw new \InvalidArgumentException('Appointment time is required');
        }
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $appointmentTime)) {
            $this->logger->warning('Invalid appointment time format');
            throw new \InvalidArgumentException('Invalid appointment time format. Use HH:MM or HH:MM:SS');
        }
    }

    private function validatePurpose(string $purpose): void
    {
        if (empty(trim($purpose))) {
            $this->logger->warning('Purpose is required');
            throw new \InvalidArgumentException('Purpose is required');
        }
        if (strlen($purpose) > 500) {
            $this->logger->warning('Purpose exceeds 500 characters');
            throw new \InvalidArgumentException('Purpose cannot exceed 500 characters');
        }
    }

    private function validateCitizenEmail(?string $citizenEmail): void
    {
        if ($citizenEmail !== null && $citizenEmail !== '') {
            if (!filter_var($citizenEmail, FILTER_VALIDATE_EMAIL)) {
                $this->logger->warning('Invalid email format');
                throw new \InvalidArgumentException('Invalid email format');
            }
            if (strlen($citizenEmail) > 255) {
                $this->logger->warning('Email exceeds 255 characters');
                throw new \InvalidArgumentException('Email cannot exceed 255 characters');
            }
        }
    }

    private function validateCitizenCCCD(?string $citizenCCCD): void
    {
        if ($citizenCCCD !== null && $citizenCCCD !== '') {
            if (strlen($citizenCCCD) > 20) {
                $this->logger->warning('Citizen CCCD exceeds 20 characters');
                throw new \InvalidArgumentException('Citizen CCCD cannot exceed 20 characters');
            }
            if (!preg_match('/^\d+$/', $citizenCCCD)) {
                $this->logger->warning('Citizen CCCD contains invalid characters');
                throw new \InvalidArgumentException('Citizen CCCD must contain only digits');
            }
        }
    }

    private function validateDepartment(?string $department): void
    {
        if ($department !== null && $department !== '') {
            if (strlen($department) > 100) {
                $this->logger->warning('Department exceeds 100 characters');
                throw new \InvalidArgumentException('Department cannot exceed 100 characters');
            }
        }
    }

    private function validateDurationMinutes(?int $durationMinutes): void
    {
        if ($durationMinutes !== null) {
            if ($durationMinutes < 1) {
                $this->logger->warning('Duration is less than 1 minute');
                throw new \InvalidArgumentException('Duration must be at least 1 minute');
            }
            if ($durationMinutes > 480) {
                $this->logger->warning('Duration exceeds 480 minutes');
                throw new \InvalidArgumentException('Duration cannot exceed 480 minutes (8 hours)');
            }
        }
    }

    private function validateNotes(?string $notes): void
    {
        if ($notes !== null && $notes !== '') {
            if (strlen($notes) > 1000) {
                $this->logger->warning('Notes exceed 1000 characters');
                throw new \InvalidArgumentException('Notes cannot exceed 1000 characters');
            }
        }
    }

    private function validateDescription(?string $description): void
    {
        if ($description !== null && $description !== '') {
            if (strlen($description) > 1000) {
                $this->logger->warning('Description exceeds 1000 characters');
                throw new \InvalidArgumentException('Description cannot exceed 1000 characters');
            }
        }
    }
 }