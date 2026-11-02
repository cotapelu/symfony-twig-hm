<?php

declare(strict_types=1);

namespace App\Tests\Integration\Appointment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Appointment\Application\Service\AppointmentService;
use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Infrastructure\Repository\AppointmentRepository;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class AppointmentIntegrationTest extends DatabaseIntegrationTestCase
{
    private AppointmentService $service;
    private AppointmentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $em = self::getEntityManager();

        $this->repo = new AppointmentRepository($em, $em->getClassMetadata(Appointment::class));

        $logger = new NullLogger();

        $this->service = new AppointmentService($this->repo, $logger);
        // Set a test user ID for authentication context
        $this->service->setUserId('test-user-001');
    }

    public function testCreateAppointmentSuccess(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W001',
            appointmentDate: '2025-06-10',
            appointmentTime: '10:00',
            citizenName: 'Nguyễn Văn A',
            citizenPhone: '0900111222',
            purpose: 'document_submission',
            description: 'Need help with paperwork'
        );

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertNotNull($appointment->getId());
        $this->assertSame('W001', $appointment->getWardCode());
        $this->assertSame('2025-06-10', $appointment->getAppointmentDate()->format('Y-m-d'));
        $this->assertSame('10:00', $appointment->getAppointmentTime());
        $this->assertSame('Nguyễn Văn A', $appointment->getCitizenName());
        $this->assertSame('document_submission', $appointment->getPurpose());
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->getStatus());

        // Verify persisted
        $found = $this->repo->findById($appointment->getId());
        $this->assertNotNull($found);
        $this->assertSame($appointment->getId(), $found->getId());
    }

    public function testConfirmAppointmentChangesStatus(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W002',
            appointmentDate: '2025-06-11',
            appointmentTime: '11:00',
            citizenName: 'Nguyễn Văn B',
            citizenPhone: '0900333444',
            purpose: 'consultation',
            description: 'General consultation'
        );

        $this->assertSame(Appointment::STATUS_PENDING, $appointment->getStatus());

        $confirmed = $this->service->confirmAppointment($appointment->getId());
        $this->assertNotNull($confirmed);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $confirmed->getStatus());
    }

    public function testCancelAppointmentChangesStatus(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W003',
            appointmentDate: '2025-06-12',
            appointmentTime: '14:00',
            citizenName: 'Nguyễn Văn C',
            citizenPhone: '0900555666',
            purpose: 'inquiry',
            description: 'Inquiry about services'
        );

        $cancelled = $this->service->cancelAppointment($appointment->getId(), 'changed mind', 'system');
        $this->assertNotNull($cancelled);
        $this->assertSame(Appointment::STATUS_CANCELLED, $cancelled->getStatus());
    }

    public function testGetAppointmentsByWardReturnsOnlyMatching(): void
    {
        $wardA = 'W-A';
        $wardB = 'W-B';

        $a1 = $this->service->createAppointment(
            wardCode: $wardA,
            appointmentDate: '2025-06-15',
            appointmentTime: '09:00',
            citizenName: 'Citizen A1',
            citizenPhone: '0901111111',
            purpose: 'inquiry',
            description: 'A1'
        );
        $a2 = $this->service->createAppointment(
            wardCode: $wardA,
            appointmentDate: '2025-06-16',
            appointmentTime: '10:00',
            citizenName: 'Citizen A2',
            citizenPhone: '0902222222',
            purpose: 'consultation',
            description: 'A2'
        );
        $b1 = $this->service->createAppointment(
            wardCode: $wardB,
            appointmentDate: '2025-06-17',
            appointmentTime: '11:00',
            citizenName: 'Citizen B1',
            citizenPhone: '0903333333',
            purpose: 'document_submission',
            description: 'B1'
        );

        $wardsA = $this->service->getAppointmentsByWard($wardA);
        $this->assertCount(2, $wardsA);
        $ids = array_map(fn($a) => $a->getId(), $wardsA);
        $this->assertContains($a1->getId(), $ids);
        $this->assertContains($a2->getId(), $ids);

        $wardsB = $this->service->getAppointmentsByWard($wardB);
        $this->assertCount(1, $wardsB);
        $this->assertSame($b1->getId(), $wardsB[0]->getId());
    }

    public function testEnsureCanAccessAppointmentAllowsAdmin(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W004',
            appointmentDate: '2025-06-18',
            appointmentTime: '13:00',
            citizenName: 'Nguyễn Văn D',
            citizenPhone: '0904444444',
            purpose: 'verification',
            description: 'Verify documents'
        );

        // Admin (role 'ROLE_ADMIN') should have access
        $this->service->ensureCanAccessAppointment($appointment, 'admin-user', 'ROLE_ADMIN');
        $this->assertTrue(true); // no exception = pass
    }

    public function testEnsureCanAccessAppointmentDeniesOtherCitizen(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W005',
            appointmentDate: '2025-06-19',
            appointmentTime: '15:00',
            citizenName: 'Nguyễn Văn E',
            citizenPhone: '0905555555',
            purpose: 'other',
            description: 'Other purpose'
        );

        // Another user (different citizen id) should not have access
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Forbidden: cannot access appointment');
        $this->service->ensureCanAccessAppointment($appointment, 'other-citizen-id', 'ROLE_CITIZEN');
    }

    public function testCreateAppointmentFailsWhenUnauthenticated(): void
    {
        $service = new AppointmentService($this->repo, new NullLogger());
        // Do NOT set user ID

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not authenticated');

        $service->createAppointment(
            wardCode: 'W001',
            appointmentDate: '2025-06-10',
            appointmentTime: '10:00',
            citizenName: 'Nguyễn Văn A',
            citizenPhone: '0900111222',
            purpose: 'document_submission'
        );
    }

    public function testInvalidDateFormatRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid appointment date format');

        $this->service->createAppointment(
            wardCode: 'W001',
            appointmentDate: 'not-a-date',
            appointmentTime: '10:00',
            citizenName: 'Citizen',
            citizenPhone: '0900111222',
            purpose: 'purpose'
        );
    }

    public function testInvalidPhoneFormatRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen phone contains invalid characters');

        $this->service->createAppointment(
            wardCode: 'W001',
            appointmentDate: '2025-06-10',
            appointmentTime: '10:00',
            citizenName: 'Citizen',
            citizenPhone: 'abc123def',
            purpose: 'purpose'
        );
    }

    public function testEmptyWardCodeRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward code is required');

        $this->service->createAppointment(
            wardCode: '',
            appointmentDate: '2025-06-10',
            appointmentTime: '10:00',
            citizenName: 'Citizen',
            citizenPhone: '0900111222',
            purpose: 'purpose'
        );
    }

    public function testStartAppointmentChangesStatusToInProgress(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W006',
            appointmentDate: '2025-06-20',
            appointmentTime: '09:00',
            citizenName: 'Citizen Start',
            citizenPhone: '0907777777',
            purpose: 'consultation'
        );
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->getStatus());

        $confirmed = $this->service->confirmAppointment($appointment->getId());
        $this->assertSame(Appointment::STATUS_CONFIRMED, $confirmed->getStatus());

        $started = $this->service->startAppointment($appointment->getId());
        $this->assertNotNull($started);
        $this->assertSame(Appointment::STATUS_IN_PROGRESS, $started->getStatus());
    }

    public function testCompleteAppointmentAddsDetails(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W007',
            appointmentDate: '2025-06-21',
            appointmentTime: '10:00',
            citizenName: 'Citizen Complete',
            citizenPhone: '0908888888',
            purpose: 'document_submission'
        );
        $this->service->confirmAppointment($appointment->getId());
        $this->service->startAppointment($appointment->getId());

        $officerNotes = 'All documents verified';
        $rating = 5;
        $feedback = 'Excellent service';

        $completed = $this->service->completeAppointment(
            $appointment->getId(),
            officerNotes: $officerNotes,
            citizenRating: $rating,
            citizenFeedback: $feedback
        );

        $this->assertNotNull($completed);
        $this->assertSame(Appointment::STATUS_COMPLETED, $completed->getStatus());
        $this->assertSame($officerNotes, $completed->getOfficerNotes());
        $this->assertSame($rating, $completed->getCitizenRating());
        $this->assertSame($feedback, $completed->getCitizenFeedback());
        $this->assertNotNull($completed->getCompletedAt());
    }

    public function testMarkNoShowChangesStatus(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W008',
            appointmentDate: '2025-06-22',
            appointmentTime: '11:00',
            citizenName: 'Citizen NoShow',
            citizenPhone: '0909999999',
            purpose: 'inquiry'
        );
        $this->service->confirmAppointment($appointment->getId());

        $noShow = $this->service->markNoShow($appointment->getId());
        $this->assertNotNull($noShow);
        $this->assertSame(Appointment::STATUS_NO_SHOW, $noShow->getStatus());
        $this->assertNotNull($noShow->getCancelledAt());
        $this->assertSame('No show', $noShow->getCancellationReason());
    }

    public function testGetTodayAppointmentsFiltersByDate(): void
    {
        $today = new \DateTimeImmutable('today');
        $todayStr = $today->format('Y-m-d');

        $a1 = $this->service->createAppointment(
            wardCode: 'W009',
            appointmentDate: $todayStr,
            appointmentTime: '13:00',
            citizenName: 'Today Citizen 1',
            citizenPhone: '0901111111',
            purpose: 'inquiry'
        );
        // Appointment for different day
        $this->service->createAppointment(
            wardCode: 'W009',
            appointmentDate: '2025-12-25',
            appointmentTime: '14:00',
            citizenName: 'Future Citizen',
            citizenPhone: '0902222222',
            purpose: 'consultation'
        );

        $todayApps = $this->service->getTodayAppointments('W009');
        $this->assertCount(1, $todayApps);
        $this->assertSame($a1->getId(), $todayApps[0]->getId());
    }

    public function testGetStatisticsReturnsCounts(): void
    {
        $ward = 'W010';
        $this->service->createAppointment(
            wardCode: $ward,
            appointmentDate: '2025-06-23',
            appointmentTime: '09:00',
            citizenName: 'Stat Citizen 1',
            citizenPhone: '0903333333',
            purpose: 'inquiry'
        );
        $a2 = $this->service->createAppointment(
            wardCode: $ward,
            appointmentDate: '2025-06-23',
            appointmentTime: '10:00',
            citizenName: 'Stat Citizen 2',
            citizenPhone: '0904444444',
            purpose: 'consultation'
        );
        $this->service->confirmAppointment($a2->getId());
        $this->service->startAppointment($a2->getId());
        $this->service->completeAppointment($a2->getId(), 'Good', 5, 'Thanks');

        $stats = $this->service->getStatistics($ward);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total']);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('completed', $stats);
    }

    public function testAssignOfficer(): void
    {
        $appointment = $this->service->createAppointment(
            wardCode: 'W-ASSIGN',
            appointmentDate: '2025-06-25',
            appointmentTime: '09:00',
            citizenName: 'Assign Citizen',
            citizenPhone: '0901234567',
            purpose: 'inquiry'
        );
        $officerId = 'officer-xyz-123';
        $assigned = $this->service->assignOfficer($appointment->getId(), $officerId);
        $this->assertNotNull($assigned);
        $this->assertSame($officerId, $assigned->getOfficerId());
    }
}
