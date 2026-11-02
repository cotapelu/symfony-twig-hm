<?php declare(strict_types=1);

namespace App\Tests\Unit\Appointment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Appointment\Application\Service\AppointmentService;
use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Domain\Repository\AppointmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

#[AllowMockObjectsWithoutExpectations]
class AppointmentServiceTest extends TestCase
{
    private AppointmentRepositoryInterface $repository;
    private LoggerInterface $logger;
    private AppointmentService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new AppointmentService($this->repository, $this->logger);
        $this->service->setUserId('user-123');
    }

    public function testCreateAppointmentSavesValidData(): void
    {
        $wardCode = 'W001';
        $citizenName = 'John Doe';
        $citizenPhone = '0987654321';
        $appointmentDate = '2025-06-10';
        $appointmentTime = '14:00';
        $purpose = 'Business registration';

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Appointment $appointment) use (
                $wardCode, $citizenName, $citizenPhone, $appointmentDate, $appointmentTime, $purpose
            ) {
                $this->assertEquals($wardCode, $appointment->getWardCode());
                $this->assertEquals($citizenName, $appointment->getCitizenName());
                $this->assertEquals($citizenPhone, $appointment->getCitizenPhone());
                $this->assertEquals($appointmentDate, $appointment->getAppointmentDate()->format('Y-m-d'));
                $this->assertEquals($appointmentTime, $appointment->getAppointmentTime());
                $this->assertEquals($purpose, $appointment->getPurpose());
                $this->assertEquals('pending', $appointment->getStatus());
                return $appointment;
            });

        $result = $this->service->createAppointment(
            $wardCode,
            $citizenName,
            $citizenPhone,
            $appointmentDate,
            $appointmentTime,
            $purpose
        );

        $this->assertInstanceOf(Appointment::class, $result);
        $this->assertEquals('pending', $result->getStatus());
    }

    public function testCreateAppointmentThrowsOnInvalidInputVarious(): void
    {
        $cases = [
            ['', 'John', '1234567890', '2025-06-10', '10:00', 'Purpose', 'Ward code is required'],
            [str_repeat('a', 21), 'John', '1234567890', '2025-06-10', '10:00', 'Purpose', 'Ward code cannot exceed 20 characters'],
            ['W001', '', '1234567890', '2025-06-10', '10:00', 'Purpose', 'Citizen name is required'],
            ['W001', str_repeat('a', 256), '1234567890', '2025-06-10', '10:00', 'Purpose', 'Citizen name cannot exceed 255 characters'],
            ['W001', 'John', '', '2025-06-10', '10:00', 'Purpose', 'Citizen phone is required'],
            ['W001', 'John', str_repeat('1', 21), '2025-06-10', '10:00', 'Purpose', 'Citizen phone cannot exceed 20 characters'],
            ['W001', 'John', '123-abc-4567', '2025-06-10', '10:00', 'Purpose', 'Citizen phone contains invalid characters'],
            ['W001', 'John', '1234567890', '', '10:00', 'Purpose', 'Appointment date is required'],
            ['W001', 'John', '1234567890', 'invalid-date', '10:00', 'Purpose', 'Invalid appointment date format'],
            ['W001', 'John', '1234567890', '2025-06-10', '', 'Purpose', 'Appointment time is required'],
            ['W001', 'John', '1234567890', '2025-06-10', '10:00', '', 'Purpose is required'],
        ];

        foreach ($cases as $case) {
            [$wardCode, $citizenName, $citizenPhone, $appointmentDate, $appointmentTime, $purpose, $expected] = $case;
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expected);
            $this->service->createAppointment($wardCode, $citizenName, $citizenPhone, $appointmentDate, $appointmentTime, $purpose);
        }
    }

    public function testCreateAppointmentThrowsIfUserNotSet(): void
    {
        $service = new AppointmentService($this->repository, $this->logger);
        // do not call setUserId

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not authenticated');

        $service->createAppointment('W001', 'John', '1234567890', '2025-06-10', '10:00', 'Purpose');
    }

    public function testGetAppointmentReturnsWhenFound(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $appointment = $this->createAppointmentEntity($id, '2025-06-10');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($appointment);

        $result = $this->service->getAppointment($id);

        $this->assertSame($appointment, $result);
    }

    public function testGetAppointmentReturnsNullWhenNotFound(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->getAppointment($id);

        $this->assertNull($result);
    }

    public function testGetAppointmentsByWardReturnsResults(): void
    {
        $wardCode = 'W001';
        $status = 'pending';
        $limit = 10;
        $appointments = [
            $this->createAppointmentEntity('id1', '2025-06-10'),
            $this->createAppointmentEntity('id2', '2025-06-11'),
        ];

        $this->repository->expects($this->once())
            ->method('findByWard')
            ->with($wardCode, $status, $limit)
            ->willReturn($appointments);

        $result = $this->service->getAppointmentsByWard($wardCode, $status, $limit);

        $this->assertCount(2, $result);
        $this->assertSame($appointments[0], $result[0]);
    }

    public function testConfirmAppointmentChangesStatus(): void
    {
        $id = 'appt-001';
        $appointment = $this->createAppointmentEntity($id, '2025-06-10');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $result = $this->service->confirmAppointment($id);

        $this->assertEquals('confirmed', $result->getStatus());
    }

    public function testCancelAppointmentChangesStatusAndReason(): void
    {
        $id = 'appt-001';
        $appointment = $this->createAppointmentEntity($id, '2025-06-10');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $result = $this->service->cancelAppointment($id, 'No longer needed', 'citizen');

        $this->assertEquals('cancelled', $result->getStatus());
        $this->assertEquals('No longer needed', $result->getCancellationReason());
    }

    private function createAppointmentEntity(string $id, string $dateString): Appointment
    {
        return new Appointment(
            id: $id,
            wardCode: 'W001',
            citizenName: 'John Doe',
            citizenPhone: '0987654321',
            appointmentDate: new DateTimeImmutable($dateString),
            appointmentTime: '10:00',
            purpose: 'purpose'
        );
    }
}
