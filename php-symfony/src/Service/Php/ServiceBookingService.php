<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\ServiceBooking;
use App\Repository\Php\ServiceBookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class ServiceBookingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ServiceBookingRepository $repository
    ) {}

    public function createBooking(
        string $citizenId,
        string $catalogItemId,
        \DateTime $bookingTime,
        int $durationMinutes,
        ?string $status = 'PENDING',
        ?string $notes = null,
        ?array $metadata = null
    ) {
        $this->validateCreateInput($citizenId, $catalogItemId, $bookingTime, $durationMinutes);
        $booking = $this->buildBookingEntity($citizenId, $catalogItemId, $bookingTime, $durationMinutes, $status, $notes, $metadata);
        $this->em->persist($booking);
        $this->em->flush();
        return $booking;
    }

    public function getBooking(string $id)
    {
        $booking = $this->repository->find($id);
        if (!$booking) throw new \RuntimeException('ServiceBooking not found');
        return $booking;
    }

    public function updateBooking(string $id, array $data)
    {
        $booking = $this->getBooking($id);
        $this->applyBookingFields($booking, $data);
        $this->em->flush();
        return $booking;
    }

    public function deleteBooking(string $id)
    {
        $booking = $this->getBooking($id);
        $this->em->remove($booking);
        $this->em->flush();
    }

    public function findByCitizenId(string $citizenId)
    {
        return $this->repository->findBy(['citizenId' => $citizenId]);
    }

    public function findByCatalogItemId(string $catalogItemId)
    {
        return $this->repository->findBy(['catalogItemId' => $catalogItemId]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    private function validateCreateInput(string $citizenId, string $catalogItemId, \DateTime $bookingTime, int $durationMinutes): void
    {
        if (empty(trim($citizenId))) throw new \InvalidArgumentException('Citizen ID is required');
        if (empty(trim($catalogItemId))) throw new \InvalidArgumentException('Catalog item ID is required');
        if ($bookingTime < new \DateTime()) throw new \InvalidArgumentException('Booking time cannot be in the past');
        if ($durationMinutes <= 0) throw new \InvalidArgumentException('Duration must be positive');
    }

    private function buildBookingEntity(
        string $citizenId,
        string $catalogItemId,
        \DateTime $bookingTime,
        int $durationMinutes,
        ?string $status,
        ?string $notes,
        ?array $metadata
    ): ServiceBooking {
        $booking = new ServiceBooking(
            bin2hex(random_bytes(16)),
            $citizenId,
            $catalogItemId,
            $bookingTime,
            $durationMinutes
        );
        $booking->setStatus($status);
        $booking->setNotes($notes);
        $booking->setMetadata($metadata);
        return $booking;
    }

    private function applyBookingFields(ServiceBooking $booking, array $data): void
    {
        $this->applyCitizenId($booking, $data);
        $this->applyCatalogItemId($booking, $data);
        $this->applyBookingTime($booking, $data);
        $this->applyDurationMinutes($booking, $data);
        $this->applyStatus($booking, $data);
        $this->applyNotes($booking, $data);
        $this->applyMetadata($booking, $data);
    }

    private function applyCitizenId(ServiceBooking $booking, array $data): void
    {
        if (!array_key_exists('citizenId', $data)) return;
        $citizenId = trim($data['citizenId']);
        if ($citizenId === '') throw new \InvalidArgumentException('Citizen ID cannot be empty');
        $booking->setCitizenId($citizenId);
    }

    private function applyCatalogItemId(ServiceBooking $booking, array $data): void
    {
        if (!array_key_exists('catalogItemId', $data)) return;
        $catalogItemId = trim($data['catalogItemId']);
        if ($catalogItemId === '') throw new \InvalidArgumentException('Catalog item ID cannot be empty');
        $booking->setCatalogItemId($catalogItemId);
    }

    private function applyBookingTime(ServiceBooking $booking, array $data): void
    {
        if (!array_key_exists('bookingTime', $data)) return;
        $bookingTime = $data['bookingTime'];
        if (!$bookingTime instanceof \DateTime) throw new \InvalidArgumentException('Booking time must be a DateTime object');
        if ($bookingTime < new \DateTime()) throw new \InvalidArgumentException('Booking time cannot be in the past');
        $booking->setBookingTime($bookingTime);
    }

    private function applyDurationMinutes(ServiceBooking $booking, array $data): void
    {
        if (!array_key_exists('durationMinutes', $data)) return;
        $duration = (int)$data['durationMinutes'];
        if ($duration <= 0) throw new \InvalidArgumentException('Duration must be positive');
        $booking->setDurationMinutes($duration);
    }

    private function applyStatus(ServiceBooking $booking, array $data): void
    {
        if (!array_key_exists('status', $data)) return;
        $status = trim($data['status']);
        if ($status === '') throw new \InvalidArgumentException('Status cannot be empty');
        $booking->setStatus($status);
    }

    private function applyNotes(ServiceBooking $booking, array $data): void
    {
        if (array_key_exists('notes', $data)) {
            $booking->setNotes($data['notes']);
        }
    }

    private function applyMetadata(ServiceBooking $booking, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $booking->setMetadata($data['metadata']);
        }
    }
}
