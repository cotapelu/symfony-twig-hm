<?php

declare(strict_types=1);

namespace Tests\Unit\Appointment\Domain\Entity;

use App\Appointment\Domain\Entity\Appointment;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

class AppointmentEntityTest extends TestCase
{
    private function makeAppointment(
        string $date = '2026-06-15',
        string $time = '09:00',
        int $duration = 30
    ): Appointment {
        return new Appointment(
            'appt-1',
            'W01',
            'Nguyen Van A',
            '0909123456',
            new DateTimeImmutable($date),
            $time,
            Appointment::PURPOSE_CONSULTATION,
        );
    }

    // ================= constructor / identity =================
    public function testConstructorInitializesState(): void
    {
        $a = $this->makeAppointment();

        $this->assertSame('appt-1', $a->getId());
        $this->assertSame(Appointment::STATUS_PENDING, $a->getStatus());
        $this->assertTrue($a->isPending());
        $this->assertSame('W01', $a->getWardCode());
        $this->assertSame('Nguyen Van A', $a->getCitizenName());
        $this->assertSame('0909123456', $a->getCitizenPhone());
        $this->assertSame(Appointment::PURPOSE_CONSULTATION, $a->getPurpose());
        $this->assertStringStartsWith('LICH-', $a->getAppointmentNumber());
        $this->assertInstanceOf(DateTimeImmutable::class, $a->getCreatedAt());
        // constructor must not set transition timestamps
        $this->assertNull($a->getConfirmedAt());
        $this->assertNull($a->getStartedAt());
        $this->assertNull($a->getCompletedAt());
        $this->assertNull($a->getCancelledAt());
        $this->assertNull($a->getOfficer());
        $this->assertNull($a->getCitizenEmail());
        $this->assertNull($a->getCitizenCCCD());
        $this->assertNull($a->getOfficerId());
        $this->assertNull($a->getCitizenUserId());
    }

    // ================= state machine: happy paths =================
    public function testConfirmFromPending(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();

        $this->assertSame(Appointment::STATUS_CONFIRMED, $a->getStatus());
        $this->assertTrue($a->isConfirmed());
        $this->assertInstanceOf(DateTimeImmutable::class, $a->getConfirmedAt());
    }

    public function testStartFromConfirmed(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();
        $a->start();

        $this->assertSame(Appointment::STATUS_IN_PROGRESS, $a->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $a->getStartedAt());
    }

    public function testCompleteStoresFeedback(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();
        $a->start();
        $a->complete('Xong nhanh', 5, 'Rat hai long');

        $this->assertSame(Appointment::STATUS_COMPLETED, $a->getStatus());
        $this->assertTrue($a->isCompleted());
        $this->assertSame('Xong nhanh', $a->getOfficerNotes());
        $this->assertSame(5, $a->getCitizenRating());
        $this->assertSame('Rat hai long', $a->getCitizenFeedback());
        $this->assertInstanceOf(DateTimeImmutable::class, $a->getCompletedAt());
    }

    public function testCompleteWithoutFeedback(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();
        $a->start();
        $a->complete();

        $this->assertTrue($a->isCompleted());
        $this->assertNull($a->getOfficerNotes());
        $this->assertNull($a->getCitizenRating());
    }

    public function testCancelStoresReason(): void
    {
        $a = $this->makeAppointment();
        $a->cancel('Ban dot xuat', 'citizen-1');

        $this->assertSame(Appointment::STATUS_CANCELLED, $a->getStatus());
        $this->assertTrue($a->isCancelled());
        $this->assertSame('Ban dot xuat', $a->getCancellationReason());
        $this->assertSame('citizen-1', $a->getCancelBy());
        $this->assertInstanceOf(DateTimeImmutable::class, $a->getCancelledAt());
    }

    public function testMarkNoShowFromConfirmed(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();
        $a->markNoShow();

        $this->assertSame(Appointment::STATUS_NO_SHOW, $a->getStatus());
        $this->assertFalse($a->isCancelled());
        $this->assertFalse($a->isCompleted());
    }

    // ================= state machine: illegal transitions =================
    public function testConfirmRejectsNonPending(): void
    {
        $a = $this->makeAppointment();
        $a->confirm(); // now confirmed

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can only confirm pending appointments');
        $a->confirm();
    }

    public function testStartRejectsNonConfirmed(): void
    {
        $a = $this->makeAppointment(); // pending

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can only start confirmed appointments');
        $a->start();
    }

    public function testCompleteRejectsNonInProgress(): void
    {
        $a = $this->makeAppointment();
        $a->confirm(); // confirmed, not in_progress

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can only complete in-progress appointments');
        $a->complete();
    }

    public function testCancelRejectsCompleted(): void
    {
        $a = $this->makeAppointment();
        $a->confirm();
        $a->start();
        $a->complete();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot cancel completed or already cancelled appointments');
        $a->cancel('Doi y', 'citizen-1');
    }

    public function testCancelRejectsAlreadyCancelled(): void
    {
        $a = $this->makeAppointment();
        $a->cancel('Lan 1', 'citizen-1');

        $this->expectException(LogicException::class);
        $a->cancel('Lan 2', 'citizen-1');
    }

    public function testMarkNoShowRejectsNonConfirmed(): void
    {
        $a = $this->makeAppointment(); // pending

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can only mark confirmed appointments as no-show');
        $a->markNoShow();
    }

    // ================= date/time logic =================
    public function testGetEndTimeAddsDuration(): void
    {
        $a = $this->makeAppointment('2026-06-15', '09:00');
        $a->setDurationMinutes(45);

        $this->assertSame('09:45', $a->getEndTime()->format('H:i'));
        $this->assertSame(45, $a->getDurationMinutes());
    }

    public function testIsFutureAndPast(): void
    {
        $future = $this->makeAppointment((new DateTimeImmutable('+30 days'))->format('Y-m-d'), '09:00');
        $this->assertTrue($future->isFuture());
        $this->assertFalse($future->isPast());

        $past = $this->makeAppointment((new DateTimeImmutable('-30 days'))->format('Y-m-d'), '09:00');
        $this->assertTrue($past->isPast());
        $this->assertFalse($past->isFuture());
    }

    public function testIsToday(): void
    {
        $today = $this->makeAppointment((new DateTimeImmutable('today'))->format('Y-m-d'), '09:00');
        $this->assertTrue($today->isToday());

        $notToday = $this->makeAppointment((new DateTimeImmutable('+3 days'))->format('Y-m-d'), '09:00');
        $this->assertFalse($notToday->isToday());
    }

    // ================= setters / accessors =================
    public function testSettersUpdateFields(): void
    {
        $a = $this->makeAppointment();
        $a->setCitizenEmail('a@example.com');
        $a->setCitizenCCCD('012345678901');
        $a->setOfficerId('OFF-1');
        $a->setWardCode('W02');
        $a->setCitizenUserId('citizen-7');
        $a->setDepartment('Tu phap');
        $a->setNotes('Mang theo ho khau');
        $a->setDescription('Mo ta');
        $a->setDurationMinutes(60);

        $this->assertSame('a@example.com', $a->getCitizenEmail());
        $this->assertSame('012345678901', $a->getCitizenCCCD());
        $this->assertSame('OFF-1', $a->getOfficerId());
        $this->assertSame('W02', $a->getWardCode());
        $this->assertSame('citizen-7', $a->getCitizenUserId());
        $this->assertSame('Tu phap', $a->getDepartment());
        $this->assertSame('Mang theo ho khau', $a->getNotes());
        $this->assertSame('Mo ta', $a->getDescription());
        $this->assertSame(60, $a->getDurationMinutes());
    }

    public function testNullableSettersAcceptNull(): void
    {
        $a = $this->makeAppointment();
        $a->setOfficerId(null);
        $a->setCitizenEmail(null);
        $a->setCitizenCCCD(null);
        $a->setNotes(null);
        $a->setDescription(null);
        $a->setDepartment(null);
        $a->setCitizen(null);

        $this->assertNull($a->getOfficerId());
        $this->assertNull($a->getCitizenEmail());
        $this->assertNull($a->getCitizenCCCD());
        $this->assertNull($a->getNotes());
        $this->assertNull($a->getDescription());
        $this->assertNull($a->getDepartment());
        $this->assertNull($a->getCitizen());
    }

    public function testToArraySerializesKeyFields(): void
    {
        $a = $this->makeAppointment('2026-06-15', '09:00', 30);
        $a->confirm();

        $arr = $a->toArray();

        $this->assertIsArray($arr);
        $this->assertSame('appt-1', $arr['id']);
        $this->assertSame('W01', $arr['wardCode']);
        $this->assertSame('Nguyen Van A', $arr['citizenName']);
        $this->assertSame('0909123456', $arr['citizenPhone']);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $arr['status']);
        $this->assertSame('09:00', $arr['appointmentTime']);
        $this->assertSame(30, $arr['durationMinutes']);
    }

    public function testVersionDefaultsToZero(): void
    {
        $this->assertSame(0, $this->makeAppointment()->getVersion());
    }
}
