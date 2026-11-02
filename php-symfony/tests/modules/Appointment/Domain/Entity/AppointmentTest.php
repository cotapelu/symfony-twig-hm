<?php

declare(strict_types=1);

namespace App\Tests\Modules\Appointment\Domain\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Appointment\Domain\Entity\Appointment;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AppointmentTest extends TestCase
{
    private function createValidAppointment(array $override = []): Appointment
    {
        $data = array_merge([
            'id' => 'appt-123',
            'wardCode' => 'W001',
            'citizenName' => 'John Doe',
            'citizenPhone' => '0123456789',
            'appointmentDate' => new DateTimeImmutable('2025-01-15'),
            'appointmentTime' => '09:00',
            'purpose' => 'consultation',
        ], $override);

        return new Appointment(
            $data['id'],
            $data['wardCode'],
            $data['citizenName'],
            $data['citizenPhone'],
            $data['appointmentDate'],
            $data['appointmentTime'],
            $data['purpose']
        );
    }

    public function testConstructorSetsInitialState(): void
    {
        $appointment = $this->createValidAppointment();

        $this->assertSame('appt-123', $appointment->getId());
        $this->assertSame('W001', $appointment->getWardCode());
        $this->assertSame('John Doe', $appointment->getCitizenName());
        $this->assertSame('0123456789', $appointment->getCitizenPhone());
        $this->assertSame('consultation', $appointment->getPurpose());
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->getStatus());
        $this->assertTrue($appointment->isPending());
        $this->assertFalse($appointment->isConfirmed());
        $this->assertFalse($appointment->isCompleted());
        $this->assertFalse($appointment->isCancelled());
        $this->assertNotNull($appointment->getCreatedAt());
    }

    public function testConfirmTransitionsStatusToConfirmed(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();

        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->getStatus());
        $this->assertTrue($appointment->isConfirmed());
        $this->assertNotNull($appointment->getConfirmedAt());
    }

    public function testConfirmThrowsIfNotPending(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm(); // first confirm

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only confirm pending appointments');
        $appointment->confirm(); // second confirm should fail
    }

    public function testStartThrowsIfNotConfirmed(): void
    {
        $appointment = $this->createValidAppointment();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only start confirmed appointments');
        $appointment->start();
    }

    public function testStartTransitionsToInProgress(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();
        $appointment->start();

        $this->assertSame(Appointment::STATUS_IN_PROGRESS, $appointment->getStatus());
        $this->assertNotNull($appointment->getStartedAt());
    }

    public function testCompleteWithNotesAndRating(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();
        $appointment->start();
        $appointment->complete('Great session', 5, 'Very helpful');

        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->getStatus());
        $this->assertSame('Great session', $appointment->getOfficerNotes());
        $this->assertSame(5, $appointment->getCitizenRating());
        $this->assertSame('Very helpful', $appointment->getCitizenFeedback());
        $this->assertNotNull($appointment->getCompletedAt());
    }

    public function testCompleteThrowsIfNotInProgress(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only complete in-progress appointments');
        $appointment->complete();
    }

    public function testCancelWhenPending(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->cancel('No longer needed', 'citizen');

        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->getStatus());
        $this->assertSame('No longer needed', $appointment->getCancellationReason());
        $this->assertSame('citizen', $appointment->getCancelBy());
        $this->assertNotNull($appointment->getCancelledAt());
    }

    public function testCancelThrowsIfCompletedOrCancelled(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();
        $appointment->start();
        $appointment->complete();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot cancel completed or already cancelled appointments');
        $appointment->cancel('reason', 'officer');
    }

    public function testMarkNoShowOnlyFromConfirmed(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only mark confirmed appointments as no-show');
        $appointment->start();
        $appointment->markNoShow(); // should fail because not confirmed
    }

    public function testMarkNoShowTransitionsToNoShow(): void
    {
        $appointment = $this->createValidAppointment();
        $appointment->confirm();
        $appointment->markNoShow();

        $this->assertSame(Appointment::STATUS_NO_SHOW, $appointment->getStatus());
        $this->assertSame('No show', $appointment->getCancellationReason());
        $this->assertSame('system', $appointment->getCancelBy());
    }

    public function testIsFutureAndPast(): void
    {
        $futureDate = new DateTimeImmutable('+1 day');
        $appointment = $this->createValidAppointment(['appointmentDate' => $futureDate]);
        $this->assertTrue($appointment->isFuture());

        $pastDate = new DateTimeImmutable('-1 day');
        $appointment = $this->createValidAppointment(['appointmentDate' => $pastDate]);
        $this->assertTrue($appointment->isPast());
    }

    public function testIsToday(): void
    {
        $today = new DateTimeImmutable('today');
        $appointment = $this->createValidAppointment(['appointmentDate' => $today]);
        $this->assertTrue($appointment->isToday());

        $tomorrow = $today->modify('+1 day');
        $appointment = $this->createValidAppointment(['appointmentDate' => $tomorrow]);
        $this->assertFalse($appointment->isToday());
    }

    public function testGetEndTime(): void
    {
        $appointment = $this->createValidAppointment();
        // Default duration is 30 minutes
        $endTime = $appointment->getEndTime();
        $this->assertSame('09:30', $endTime->format('H:i')); // 09:00 + 30 minutes

        // Test custom duration
        $appointment->setDurationMinutes(45);
        $endTime = $appointment->getEndTime();
        $this->assertSame('09:45', $endTime->format('H:i'));
    }

    public function testVersionIsInitiallyZero(): void
    {
        $appointment = $this->createValidAppointment();
        $this->assertSame(0, $appointment->getVersion());
    }

    // Note: Version increments are handled by Doctrine on flush, not visible in pure entity tests
}
