<?php

declare(strict_types=1);

namespace Tests\Unit\Appointment\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Appointment\Application\Service\AppointmentService;
use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Domain\Repository\AppointmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

#[AllowMockObjectsWithoutExpectations]
class AppointmentServiceTest extends TestCase
{
    private MockObject $repository;
    private MockObject $logger;
    private AppointmentService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new AppointmentService($this->repository, $this->logger);
        $this->service->setUserId('user-123');
    }

    public function testCreateAppointmentSuccess(): void
    {
        $appointmentId = 'appt-uuid';
        $date = new DateTimeImmutable('2025-01-15');
        $time = '10:00';

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment created successfully', $this->arrayHasKey('appointmentId'));

        // Mock repository save to accept any Appointment
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Appointment::class));

        $result = $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John Doe',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Consultation'
        );

        $this->assertInstanceOf(Appointment::class, $result);
        $this->assertNotEmpty($result->getId());
    }

    public function testCreateAppointmentFailsWhenUnauthenticated(): void
    {
        // Create new service without setUserId (null by default)
        $service = new AppointmentService($this->repository, $this->logger);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User not authenticated for appointment creation');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not authenticated');

        $service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenWardCodeEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ward code is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward code is required');

        $this->service->createAppointment(
            wardCode: '',
            citizenName: 'John',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenPhoneInvalidChars(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Citizen phone contains invalid characters');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen phone contains invalid characters');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '0123-abc', // invalid chars
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testGetAppointmentFound(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment retrieved successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->getAppointment('appt-123');

        $this->assertSame($appointment, $result);
    }

    public function testGetAppointmentNotFoundReturnsNull(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('notfound')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', $this->arrayHasKey('appointmentId'));

        $result = $this->service->getAppointment('notfound');

        $this->assertNull($result);
    }

    public function testConfirmAppointmentSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())->method('confirm');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment confirmed successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->confirmAppointment('appt-123');

        $this->assertSame($appointment, $result);
    }

    public function testCancelAppointmentSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())->method('cancel')->with('Too late', 'citizen');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $result = $this->service->cancelAppointment('appt-123', 'Too late', 'citizen');

        self::assertSame($appointment, $result);
    }

    public function testGetAppointmentsByWardReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findByWard')
            ->with('W001', null, null)
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve appointments by ward', $this->arrayHasKey('error'));

        $result = $this->service->getAppointmentsByWard('W001');

        $this->assertSame([], $result);
    }

    public function testEnsureCanAccessAppointmentAllowsAdmin(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $this->service->ensureCanAccessAppointment($appointment, 'admin-user', 'ROLE_ADMIN');
        $this->assertTrue(true); // no exception
    }

    public function testEnsureCanAccessAppointmentDeniesCitizenOtherAppointment(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getCitizenUserId')->willReturn('other-user');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Forbidden: cannot access appointment');

        $this->service->ensureCanAccessAppointment($appointment, 'user-123', 'ROLE_CITIZEN');
    }

    public function testIsAppointmentOwnedByCitizen(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getCitizenUserId')->willReturn('user-123');

        $result = $this->service->isAppointmentOwnedByCitizen($appointment, 'user-123');
        $this->assertTrue($result);

        $result = $this->service->isAppointmentOwnedByCitizen($appointment, 'other-user');
        $this->assertFalse($result);
    }

    // ==================== getAppointmentByNumber tests ====================

    public function testGetAppointmentByNumberFound(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');

        $this->repository->expects($this->once())
            ->method('findByNumber')
            ->with('APPT-001')
            ->willReturn($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment retrieved by number successfully', $this->arrayHasKey('appointmentNumber'));

        $result = $this->service->getAppointmentByNumber('APPT-001');
        $this->assertSame($appointment, $result);
    }

    public function testGetAppointmentByNumberNotFoundReturnsNull(): void
    {
        $this->repository->expects($this->once())
            ->method('findByNumber')
            ->with('UNKNOWN')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found by number', $this->arrayHasKey('appointmentNumber'));

        $result = $this->service->getAppointmentByNumber('UNKNOWN');
        $this->assertNull($result);
    }

    // ==================== getAppointmentsByWard tests ====================

    public function testGetAppointmentsByWardReturnsList(): void
    {
        $appt1 = $this->createMock(Appointment::class);
        $appt2 = $this->createMock(Appointment::class);
        $list = [$appt1, $appt2];

        $this->repository->expects($this->once())
            ->method('findByWard')
            ->with('W001', null, null)
            ->willReturn($list);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointments retrieved by ward', $this->arrayHasKey('count'));

        $result = $this->service->getAppointmentsByWard('W001');
        $this->assertSame($list, $result);
    }

    public function testGetAppointmentsByWardWithFilters(): void
    {
        $list = [];
        $this->repository->expects($this->once())
            ->method('findByWard')
            ->with('W001', 'confirmed', 10)
            ->willReturn($list);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointments retrieved by ward', $this->arrayHasKey('count'));

        $result = $this->service->getAppointmentsByWard('W001', 'confirmed', 10);
        $this->assertSame($list, $result);
    }

    // ==================== getAppointmentsByOfficer tests ====================

    public function testGetAppointmentsByOfficerReturnsList(): void
    {
        $appt = $this->createMock(Appointment::class);
        $this->repository->expects($this->once())
            ->method('findByOfficer')
            ->with('officer-1', null)
            ->willReturn([$appt]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointments retrieved by officer', $this->arrayHasKey('count'));

        $result = $this->service->getAppointmentsByOfficer('officer-1');
        $this->assertCount(1, $result);
    }

    public function testGetAppointmentsByOfficerWithStatus(): void
    {
        $this->repository->expects($this->once())
            ->method('findByOfficer')
            ->with('officer-1', 'completed')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointments retrieved by officer', $this->arrayHasKey('count'));

        $result = $this->service->getAppointmentsByOfficer('officer-1', 'completed');
        $this->assertSame([], $result);
    }

    public function testGetAppointmentsByOfficerReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findByOfficer')
            ->with('officer-1', null)
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve appointments by officer', $this->arrayHasKey('error'));

        $result = $this->service->getAppointmentsByOfficer('officer-1');
        $this->assertSame([], $result);
    }

    // ==================== getAppointmentsByPhone tests ====================

    public function testGetAppointmentsByPhoneReturnsList(): void
    {
        $appt = $this->createMock(Appointment::class);
        $this->repository->expects($this->once())
            ->method('findByPhone')
            ->with('0123456789')
            ->willReturn([$appt]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointments retrieved by phone', $this->arrayHasKey('count'));

        $result = $this->service->getAppointmentsByPhone('0123456789');
        $this->assertCount(1, $result);
    }

    public function testGetAppointmentsByPhoneReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findByPhone')
            ->with('0123456789')
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve appointments by phone', $this->arrayHasKey('error'));

        $result = $this->service->getAppointmentsByPhone('0123456789');
        $this->assertSame([], $result);
    }

    // ==================== getAvailableSlots tests ====================

    public function testGetAvailableSlotsReturnsList(): void
    {
        $slot1 = $this->createMock(Appointment::class);
        $this->repository->expects($this->once())
            ->method('findAvailableSlots')
            ->with('W001', '2025-01-15')
            ->willReturn([$slot1]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Available slots retrieved', $this->arrayHasKey('count'));

        $result = $this->service->getAvailableSlots('W001', '2025-01-15');
        $this->assertCount(1, $result);
    }

    public function testGetAvailableSlotsReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findAvailableSlots')
            ->with('W001', '2025-01-15')
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve available slots', $this->arrayHasKey('error'));

        $result = $this->service->getAvailableSlots('W001', '2025-01-15');
        $this->assertSame([], $result);
    }

    // ==================== getUpcomingAppointments tests ====================

    public function testGetUpcomingAppointmentsReturnsList(): void
    {
        $appt = $this->createMock(Appointment::class);
        $this->repository->expects($this->once())
            ->method('findUpcomingAppointments')
            ->with(null)
            ->willReturn([$appt]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Upcoming appointments retrieved', $this->arrayHasKey('count'));

        $result = $this->service->getUpcomingAppointments();
        $this->assertCount(1, $result);
    }

    public function testGetUpcomingAppointmentsForOfficer(): void
    {
        $this->repository->expects($this->once())
            ->method('findUpcomingAppointments')
            ->with('officer-1')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Upcoming appointments retrieved', $this->arrayHasKey('count'));

        $result = $this->service->getUpcomingAppointments('officer-1');
        $this->assertSame([], $result);
    }

    public function testGetUpcomingAppointmentsReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findUpcomingAppointments')
            ->with(null)
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve upcoming appointments', $this->arrayHasKey('error'));

        $result = $this->service->getUpcomingAppointments();
        $this->assertSame([], $result);
    }

    // ==================== getTodayAppointments tests ====================

    public function testGetTodayAppointmentsReturnsList(): void
    {
        $appt = $this->createMock(Appointment::class);
        $this->repository->expects($this->once())
            ->method('findTodayAppointments')
            ->with('W001', null)
            ->willReturn([$appt]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Today appointments retrieved', $this->arrayHasKey('count'));

        $result = $this->service->getTodayAppointments('W001');
        $this->assertCount(1, $result);
    }

    public function testGetTodayAppointmentsWithOfficer(): void
    {
        $this->repository->expects($this->once())
            ->method('findTodayAppointments')
            ->with('W001', 'officer-1')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Today appointments retrieved', $this->arrayHasKey('count'));

        $result = $this->service->getTodayAppointments('W001', 'officer-1');
        $this->assertSame([], $result);
    }

    public function testGetTodayAppointmentsReturnsEmptyArrayOnException(): void
    {
        $this->repository->expects($this->once())
            ->method('findTodayAppointments')
            ->with('W001', null)
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve today appointments', $this->arrayHasKey('error'));

        $result = $this->service->getTodayAppointments('W001');
        $this->assertSame([], $result);
    }

    // ==================== startAppointment tests ====================

    public function testStartAppointmentSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())->method('start');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment started successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->startAppointment('appt-123');
        $this->assertSame($appointment, $result);
    }

    public function testStartAppointmentNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', $this->arrayHasKey('appointmentId'));

        $result = $this->service->startAppointment('missing');
        $this->assertNull($result);
    }

    // ==================== completeAppointment tests ====================

    public function testCompleteAppointmentSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())
            ->method('complete')
            ->with('Good service', 5, 'Thanks');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment completed successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->completeAppointment('appt-123', 'Good service', 5, 'Thanks');
        $this->assertSame($appointment, $result);
    }

    public function testCompleteAppointmentNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', $this->arrayHasKey('appointmentId'));

        $result = $this->service->completeAppointment('missing');
        $this->assertNull($result);
    }

    // ==================== assignOfficer tests ====================

    public function testAssignOfficerSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())
            ->method('setOfficerId')
            ->with('officer-1');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Officer assigned to appointment successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->assignOfficer('appt-123', 'officer-1');
        $this->assertSame($appointment, $result);
    }

    public function testAssignOfficerNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', $this->arrayHasKey('appointmentId'));

        $result = $this->service->assignOfficer('missing', 'officer-1');
        $this->assertNull($result);
    }

    // ==================== markNoShow tests ====================

    public function testMarkNoShowSuccess(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')->willReturn('appt-123');
        $appointment->expects($this->once())->method('markNoShow');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with('appt-123')
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment marked as no show successfully', $this->arrayHasKey('appointmentId'));

        $result = $this->service->markNoShow('appt-123');
        $this->assertSame($appointment, $result);
    }

    public function testMarkNoShowNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', $this->arrayHasKey('appointmentId'));

        $result = $this->service->markNoShow('missing');
        $this->assertNull($result);
    }

    // ==================== getStatistics tests ====================

    public function testGetStatisticsReturnsData(): void
    {
        $stats = ['total' => 5, 'completed' => 3];
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-31');

        $this->repository->expects($this->once())
            ->method('getStatistics')
            ->with('W001', $start, $end)
            ->willReturn($stats);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Statistics retrieved', $this->arrayHasKey('wardCode'));

        $result = $this->service->getStatistics('W001', $start, $end);
        $this->assertSame($stats, $result);
    }

    public function testGetStatisticsReturnsEmptyArrayOnException(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-31');

        $this->repository->expects($this->once())
            ->method('getStatistics')
            ->with('W001', $start, $end)
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve statistics', $this->arrayHasKey('error'));

        $result = $this->service->getStatistics('W001', $start, $end);
        $this->assertSame([], $result);
    }

    // ==================== isAppointmentOwnedByOfficer tests ====================

    public function testIsAppointmentOwnedByOfficer(): void
    {
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getOfficerId')->willReturn('officer-1');

        $this->assertTrue($this->service->isAppointmentOwnedByOfficer($appointment, 'officer-1'));
        $this->assertFalse($this->service->isAppointmentOwnedByOfficer($appointment, 'other'));
    }

    // ==================== createAppointment additional validation tests ====================

    public function testCreateAppointmentFailsWhenCitizenNameEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Citizen name is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen name is required');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: '',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenPhoneEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Citizen phone is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen phone is required');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenDateEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment date is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment date is required');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '0123456789',
            appointmentDate: '',
            appointmentTime: '10:00',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenTimeEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment time is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment time is required');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '',
            purpose: 'Test'
        );
    }

    public function testCreateAppointmentFailsWhenPurposeEmpty(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Purpose is required');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Purpose is required');

        $this->service->createAppointment(
            wardCode: 'W001',
            citizenName: 'John',
            citizenPhone: '0123456789',
            appointmentDate: '2025-01-15',
            appointmentTime: '10:00',
            purpose: ''
        );
    }
}

