<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ServiceBooking;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceBookingTest extends TestCase
{
    private function createBooking(
        string $id = 'booking-id',
        string $citizenId = 'citizen-1',
        string $catalogItemId = 'item-1',
        ?\DateTime $bookingTime = null,
        int $durationMinutes = 60
    ): ServiceBooking {
        $bookingTime ??= new \DateTime();
        return new ServiceBooking($id, $citizenId, $catalogItemId, $bookingTime, $durationMinutes);
    }

    public function testConstructor(): void
    {
        $time = new \DateTime('2025-01-01 10:00:00');
        $b = $this->createBooking('booking-1', 'citizen-1', 'item-1', $time, 90);
        $this->assertSame('booking-1', $b->getId());
        $this->assertSame('citizen-1', $b->getCitizenId());
        $this->assertSame('item-1', $b->getCatalogItemId());
        $this->assertSame($time, $b->getBookingTime());
        $this->assertSame(90, $b->getDurationMinutes());
        $this->assertSame('PENDING', $b->getStatus());
        $this->assertNull($b->getNotes());
        $this->assertNull($b->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $b->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $b->getUpdatedAt());
    }

    public function testSetCitizenId(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $b->setCitizenId('citizen-2');
        $this->assertSame('citizen-2', $b->getCitizenId());
        $this->assertNotEquals($original, $b->getUpdatedAt());
    }

    public function testSetCatalogItemId(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $b->setCatalogItemId('item-2');
        $this->assertSame('item-2', $b->getCatalogItemId());
        $this->assertNotEquals($original, $b->getUpdatedAt());
    }

    public function testSetBookingTime(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $newTime = new \DateTime('2025-12-31 23:59:00');
        $b->setBookingTime($newTime);
        $this->assertSame($newTime, $b->getBookingTime());
        $this->assertNotEquals($original, $b->getUpdatedAt());
    }

    public function testSetDurationMinutes(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $b->setDurationMinutes(120);
        $this->assertSame(120, $b->getDurationMinutes());
        $this->assertNotEquals($original, $b->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $b->setStatus('CONFIRMED');
        $this->assertSame('CONFIRMED', $b->getStatus());
        $this->assertNotEquals($original, $b->getUpdatedAt());
    }

    public function testSetNotes(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $b->setNotes('Please reschedule if raining');
        $this->assertSame('Please reschedule if raining', $b->getNotes());
        $this->assertNotEquals($original, $b->getUpdatedAt());
        $b->setNotes(null);
        $this->assertNull($b->getNotes());
    }

    public function testSetMetadata(): void
    {
        $b = $this->createBooking();
        $original = $b->getUpdatedAt();
        usleep(1000);
        $meta = ['reminder' => true];
        $b->setMetadata($meta);
        $this->assertSame($meta, $b->getMetadata());
        $this->assertNotEquals($original, $b->getUpdatedAt());
        $b->setMetadata(null);
        $this->assertNull($b->getMetadata());
    }
}
