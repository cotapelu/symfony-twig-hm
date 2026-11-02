<?php
declare(strict_types=1);

namespace App\Tests\Unit\Appointment\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Appointment\Application\Service\AppointmentService;
use App\Appointment\Domain\Entity\Appointment;
use App\Appointment\Domain\Repository\AppointmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use LogicException;

#[AllowMockObjectsWithoutExpectations]
class AppointmentServiceTest extends TestCase
{
    private AppointmentRepositoryInterface $repository;
    private LoggerInterface $logger;
    private AppointmentService $service;
    private string $testUserId = 'test-user-id';

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new AppointmentService($this->repository, $this->logger);
        $this->service->setUserId($this->testUserId);
    }

    public function testCreateAppointmentSuccess(): void
    {
        $wardCode = 'WARD001';
        $citizenName = 'John Doe';
        $citizenPhone = '1234567890';
        $appointmentDate = '2026-03-30';
        $appointmentTime = '14:30';
        $purpose = 'Consultation';

        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')
            ->willReturn(Uuid::uuid4()->toString());

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->logicalAnd(
                $this->isInstanceOf(Appointment::class)
            ));

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->service->createAppointment(
            $wardCode,
            $citizenName,
            $citizenPhone,
            $appointmentDate,
            $appointmentTime,
            $purpose
        );

        $this->assertInstanceOf(Appointment::class, $result);
        $this->assertMatchesRegularExpression('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $result->getId());
    }

    public function testCreateAppointmentUnauthenticatedUser(): void
    {
        $unauthenticatedService = new AppointmentService($this->repository, $this->logger);
        // Do not set userId to simulate unauthenticated state

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not authenticated');

        $unauthenticatedService->createAppointment(
            'WARD001',
            'John Doe',
            '1234567890',
            '2026-03-30',
            '14:30',
            'Consultation'
        );
    }

    public function testCreateAppointmentInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service->createAppointment(
            'WARD001',
            'John Doe',
            '1234567890',
            '2026-03-30',
            '14:30',
            'Consultation',
            'invalid-email'
        );
    }

    public function testGetAppointmentSuccess(): void
    {
        $appointmentId = 'test-appointment-id';
        $appointment = $this->createMock(Appointment::class);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($appointmentId)
            ->willReturn($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment retrieved successfully', ['appointmentId' => $appointmentId]);

        $result = $this->service->getAppointment($appointmentId);

        $this->assertSame($appointment, $result);
    }

    public function testGetAppointmentNotFound(): void
    {
        $appointmentId = 'non-existent-id';

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($appointmentId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Appointment not found', ['appointmentId' => $appointmentId]);

        $result = $this->service->getAppointment($appointmentId);

        $this->assertNull($result);
    }

    public function testConfirmAppointmentSuccess(): void
    {
        $appointmentId = 'test-id';
        $appointment = $this->createMock(Appointment::class);
        $appointment->method('getId')
            ->willReturn($appointmentId);
        $appointment->expects($this->once())
            ->method('confirm');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($appointmentId)
            ->willReturn($appointment);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($appointment);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Appointment confirmed successfully', ['appointmentId' => $appointmentId]);

        $result = $this->service->confirmAppointment($appointmentId);

        $this->assertSame($appointment, $result);
    }
}