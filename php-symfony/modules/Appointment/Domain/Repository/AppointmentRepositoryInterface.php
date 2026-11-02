<?php

declare(strict_types=1);

namespace App\Appointment\Domain\Repository;

use App\Appointment\Domain\Entity\Appointment;

interface AppointmentRepositoryInterface
{
    public function findById(string $id): ?Appointment;

    public function findByNumber(string $number): ?Appointment;

    public function findByWard(string $wardCode, ?string $status = null, ?int $limit = null): array;

    public function findByOfficer(string $officerId, ?string $status = null): array;

    public function findByPhone(string $phone): array;

    public function findAvailableSlots(string $wardCode, string $date): array;

    public function findUpcomingAppointments(?string $officerId = null): array;

    public function findTodayAppointments(string $wardCode, ?string $officerId = null): array;

    public function save(Appointment $appointment): void;

    public function remove(Appointment $appointment): void;

    public function getStatistics(string $wardCode, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array;
}
