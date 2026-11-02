<?php

declare(strict_types=1);

namespace App\Tests\Unit\Business;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\Service\Php\ServiceBookingService;
use App\Entity\Php\ServiceBooking;
use App\Repository\Php\ServiceBookingRepository;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class ServiceBookingServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ServiceBookingRepository $repository;
    private ServiceBookingService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ServiceBookingRepository::class);
        $this->service = new ServiceBookingService($this->em, $this->repository);
    }

    public function testCreateBooking_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $bookingTime = new \DateTime('+1 day');
        $result = $this->service->createBooking(
            'citizen-123',
            'catalog-456',
            $bookingTime,
            60,
            'CONFIRMED',
            'Special request',
            ['priority' => 'high']
        );

        $this->assertInstanceOf(ServiceBooking::class, $result);
        $this->assertEquals('citizen-123', $result->getCitizenId());
        $this->assertEquals('catalog-456', $result->getCatalogItemId());
        $this->assertEquals($bookingTime, $result->getBookingTime());
        $this->assertEquals(60, $result->getDurationMinutes());
        $this->assertEquals('CONFIRMED', $result->getStatus());
    }

    public function testCreateBooking_EmptyCitizen_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBooking('', 'catalog', new \DateTime('+1 day'), 60);
    }

    public function testCreateBooking_PastTime_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBooking('citizen', 'catalog', new \DateTime('-1 day'), 60);
    }

    public function testCreateBooking_InvalidDuration_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createBooking('citizen', 'catalog', new \DateTime('+1 day'), 0);
    }

    public function testGetBooking_Exists(): void
    {
        $booking = new ServiceBooking('id-1', 'citizen-1', 'catalog-1', new \DateTime('+1 day'), 60);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($booking);
        $result = $this->service->getBooking('id-1');
        $this->assertSame($booking, $result);
    }

    public function testGetBooking_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getBooking('missing');
    }

    public function testUpdateBooking_Success(): void
    {
        $booking = new ServiceBooking('id-1', 'citizen-1', 'catalog-1', new \DateTime('+1 day'), 60);
        $this->repository->expects($this->once())->method('find')->willReturn($booking);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateBooking('id-1', [
            'status' => 'CANCELLED',
            'notes' => 'Cancelled by user',
        ]);

        $this->assertEquals('CANCELLED', $result->getStatus());
        $this->assertEquals('Cancelled by user', $result->getNotes());
    }

    public function testUpdateBooking_InvalidBookingTime_ThrowsException(): void
    {
        $booking = new ServiceBooking('id-1', 'citizen-1', 'catalog-1', new \DateTime('+1 day'), 60);
        $this->repository->expects($this->once())->method('find')->willReturn($booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateBooking('id-1', ['bookingTime' => new \DateTime('-1 day')]);
    }

    public function testUpdateBooking_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateBooking('missing', ['status' => 'NEW']);
    }

    public function testDeleteBooking_Success(): void
    {
        $booking = new ServiceBooking('id-1', 'citizen-1', 'catalog-1', new \DateTime('+1 day'), 60);
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($booking);
        $this->em->expects($this->once())->method('remove')->with($booking);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteBooking('id-1');
    }

    public function testFindByCitizenId_ReturnsArray(): void
    {
        $bookings = [new ServiceBooking('id-1', 'citizen-123', 'catalog-1', new \DateTime('+1 day'), 60)];
        $this->repository->expects($this->once())->method('findBy')->with(['citizenId' => 'citizen-123'])->willReturn($bookings);
        $result = $this->service->findByCitizenId('citizen-123');
        $this->assertCount(1, $result);
    }

    public function testFindByCatalogItemId_ReturnsArray(): void
    {
        $bookings = [new ServiceBooking('id-1', 'citizen-1', 'catalog-456', new \DateTime('+1 day'), 60)];
        $this->repository->expects($this->once())->method('findBy')->with(['catalogItemId' => 'catalog-456'])->willReturn($bookings);
        $result = $this->service->findByCatalogItemId('catalog-456');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $bookings = [new ServiceBooking('id-1', 'citizen-1', 'catalog-1', new \DateTime('+1 day'), 60)];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'PENDING'])->willReturn($bookings);
        $result = $this->service->findByStatus('PENDING');
        $this->assertCount(1, $result);
    }
}
