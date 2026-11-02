<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ServiceBooking;
use App\Repository\Php\ServiceBookingRepository;
use App\Service\Php\ServiceBookingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceBookingServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ServiceBookingRepository&MockObject $repository;
    private ServiceBookingService $service;

    private string $bookingId;
    private ServiceBooking $booking;
    private \DateTime $futureTime;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ServiceBookingRepository::class);
        $this->service = new ServiceBookingService($this->em, $this->repository);

        $this->bookingId = bin2hex(random_bytes(16));
        $this->futureTime = new \DateTime('+1 day');
        $this->booking = new ServiceBooking(
            $this->bookingId,
            'citizen-1',
            'item-1',
            $this->futureTime,
            60
        );
        $this->booking->setStatus('CONFIRMED');
    }

    public function testCreateBookingSuccess(): void
    {
        $citizenId = 'cit-123';
        $catalogItemId = 'item-123';
        $bookingTime = new \DateTime('tomorrow');
        $durationMinutes = 90;
        $status = 'PENDING';
        $notes = 'Please be on time';
        $metadata = ['priority' => true];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ServiceBooking $b) use (&$captured, $citizenId, $catalogItemId, $bookingTime, $durationMinutes, $status, $notes, $metadata) {
                $captured = $b;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBooking($citizenId, $catalogItemId, $bookingTime, $durationMinutes, $status, $notes, $metadata);
        $this->assertInstanceOf(ServiceBooking::class, $result);
        $this->assertSame($citizenId, $result->getCitizenId());
        $this->assertSame($catalogItemId, $result->getCatalogItemId());
        $this->assertInstanceOf(\DateTime::class, $result->getBookingTime());
        $this->assertSame($durationMinutes, $result->getDurationMinutes());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($notes, $result->getNotes());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateBookingValidationEmptyCitizenId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID is required');
        $this->service->createBooking('', 'item', $this->futureTime, 30);
    }

    public function testCreateBookingValidationEmptyCatalogItemId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Catalog item ID is required');
        $this->service->createBooking('cit', '', $this->futureTime, 30);
    }

    public function testCreateBookingValidationTimeInPast(): void
    {
        $past = new \DateTime('-1 hour');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking time cannot be in the past');
        $this->service->createBooking('cit', 'item', $past, 30);
    }

    public function testCreateBookingValidationDurationNotPositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be positive');
        $this->service->createBooking('cit', 'item', $this->futureTime, 0);
    }

    public function testGetBookingSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $result = $this->service->getBooking($this->bookingId);
        $this->assertSame($this->booking, $result);
    }

    public function testGetBookingNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ServiceBooking not found');
        $this->service->getBooking($this->bookingId);
    }

    public function testUpdateBookingSuccess(): void
    {
        $newCitizenId = 'cit-2';
        $newCatalogItemId = 'item-2';
        $newTime = new \DateTime('+2 days');
        $newDuration = 120;
        $newStatus = 'CANCELLED';
        $newNotes = 'Changed mind';
        $newMetadata = ['reason' => 'schedule conflict'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'citizenId' => $newCitizenId,
            'catalogItemId' => $newCatalogItemId,
            'bookingTime' => $newTime,
            'durationMinutes' => $newDuration,
            'status' => $newStatus,
            'notes' => $newNotes,
            'metadata' => $newMetadata,
        ];
        $result = $this->service->updateBooking($this->bookingId, $data);
        $this->assertSame($this->booking, $result);
        $this->assertSame($newCitizenId, $result->getCitizenId());
        $this->assertSame($newCatalogItemId, $result->getCatalogItemId());
        $this->assertSame($newTime, $result->getBookingTime());
        $this->assertSame($newDuration, $result->getDurationMinutes());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newNotes, $result->getNotes());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateBookingValidationEmptyCitizenId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID cannot be empty');
        $this->service->updateBooking($this->bookingId, ['citizenId' => '']);
    }

    public function testUpdateBookingValidationEmptyCatalogItemId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Catalog item ID cannot be empty');
        $this->service->updateBooking($this->bookingId, ['catalogItemId' => '']);
    }

    public function testUpdateBookingValidationInvalidBookingTimeType(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking time must be a DateTime object');
        $this->service->updateBooking($this->bookingId, ['bookingTime' => '2025-01-01']);
    }

    public function testUpdateBookingValidationTimeInPast(): void
    {
        $past = new \DateTime('-1 hour');
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking time cannot be in the past');
        $this->service->updateBooking($this->bookingId, ['bookingTime' => $past]);
    }

    public function testUpdateBookingValidationDurationNotPositive(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be positive');
        $this->service->updateBooking($this->bookingId, ['durationMinutes' => -5]);
    }

    public function testUpdateBookingNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateBooking($this->bookingId, []);
    }

    public function testDeleteBookingSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn($this->booking);
        $this->em->expects($this->once())->method('remove')->with($this->booking);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteBooking($this->bookingId);
    }

    public function testDeleteBookingNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->bookingId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteBooking($this->bookingId);
    }

    public function testFindByCitizenId(): void
    {
        $citizenId = 'cit-123';
        $results = [$this->booking];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['citizenId' => $citizenId])
            ->willReturn($results);

        $result = $this->service->findByCitizenId($citizenId);
        $this->assertSame($results, $result);
    }

    public function testFindByCatalogItemId(): void
    {
        $itemId = 'item-123';
        $results = [$this->booking];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['catalogItemId' => $itemId])
            ->willReturn($results);

        $result = $this->service->findByCatalogItemId($itemId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'COMPLETED';
        $results = [$this->booking];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
