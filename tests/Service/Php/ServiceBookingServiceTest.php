<?php declare(strict_types=1);

namespace App\Tests\Service\Php;

use App\Entity\Php\Business;
use App\Entity\Php\ServiceBooking;
use App\Service\Php\ServiceBookingService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ServiceBookingServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private ServiceBookingService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ServiceBookingService($this->em, $this->logger);
    }

    public function testCreateBookingSuccess(): void
    {
        $userId = 'user_123';
        $businessId = 'biz_456';
        $serviceName = 'Haircut';
        $bookingDate = new \DateTime('next Monday');
        $timeSlot = '10:00-11:00';
        $customerName = 'John Doe';
        $customerPhone = '0123456789';
        $customerEmail = 'john@example.com';
        $notes = 'Long hair';
        $metadata = ['preferred_stylist' => 'Anna'];

        $business = new Business($businessId, 'Salon', 'Beauty');

        $bizRepo = $this->createMock(ObjectRepository::class);
        $bizRepo->expects($this->once())
            ->method('find')
            ->with($businessId)
            ->willReturn($business);

        $bookingRepo = $this->createMock(ObjectRepository::class);
        $bookingRepo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Business::class)
            ->willReturn($bizRepo);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($bookingRepo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ServiceBooking $b) use (
                $userId, $businessId, $serviceName, $bookingDate, $timeSlot, $customerName, $customerPhone, $customerEmail, $notes, $metadata
            ) {
                return $b->getUserId() === $userId
                    && $b->getBusinessId() === $businessId
                    && $b->getServiceName() === $serviceName
                    && $b->getBookingDate() === $bookingDate
                    && $b->getTimeSlot() === $timeSlot
                    && $b->getCustomerName() === $customerName
                    && $b->getCustomerPhone() === $customerPhone
                    && $b->getCustomerEmail() === $customerEmail
                    && $b->getNotes() === $notes
                    && $b->getMetadata() === $metadata
                    && $b->getStatus() === 'PENDING';
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Service booking created', $this->anything());

        $result = $this->service->createBooking(
            $userId, $businessId, $serviceName, $bookingDate, $timeSlot,
            $customerName, $customerPhone, $customerEmail, $notes, $metadata
        );
        $this->assertInstanceOf(ServiceBooking::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($businessId, $result->getBusinessId());
        $this->assertSame('PENDING', $result->getStatus());
    }

    public function testCreateBookingBusinessNotFound(): void
    {
        $bizRepo = $this->createMock(ObjectRepository::class);
        $bizRepo->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Business::class)
            ->willReturn($bizRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business not found');
        $this->service->createBooking('user', 'biz', 'service', new \DateTime(), 'slot');
    }

    public function testGetBooking(): void
    {
        $id = 'booking_123';
        $booking = new ServiceBooking($id, 'user', 'biz', 'service', new \DateTime(), 'slot');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getBooking($id);
        $this->assertSame($booking, $result);
    }

    public function testGetBookingsByUser(): void
    {
        $userId = 'user_123';
        $bookings = [
            new ServiceBooking('id1', $userId, 'biz1', 'service1', new \DateTime(), 'slot1'),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['bookingDate' => 'DESC'])
            ->willReturn($bookings);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getBookingsByUser($userId);
        $this->assertCount(1, $result);
    }

    public function testGetBookingsByBusiness(): void
    {
        $businessId = 'biz_123';
        $bookings = [
            new ServiceBooking('id1', 'user', $businessId, 'service', new \DateTime(), 'slot'),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['businessId' => $businessId], ['bookingDate' => 'DESC'])
            ->willReturn($bookings);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getBookingsByBusiness($businessId);
        $this->assertCount(1, $result);
    }

    public function testGetBookingsByStatus(): void
    {
        $status = 'CONFIRMED';
        $bookings = [
            new ServiceBooking('id1', 'user', 'biz', 'service', new \DateTime(), 'slot', $status),
        ];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['bookingDate' => 'DESC'])
            ->willReturn($bookings);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getBookingsByStatus($status);
        $this->assertCount(1, $result);
    }

    public function testGetBookingsByDate(): void
    {
        $date = new \DateTime('2025-06-15');
        $bookings = [new ServiceBooking('id1', 'user', 'biz', 'service', new \DateTime(), 'slot')];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('b.bookingDate >= :start')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('b.bookingDate <= :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($bookings);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getBookingsByDate($date);
        $this->assertCount(1, $result);
    }

    public function testConfirmBooking(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $booking->setStatus('PENDING');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->confirmBooking($bookingId);
        $this->assertSame($booking, $result);
        $this->assertSame('CONFIRMED', $result->getStatus());
    }

    public function testCancelBooking(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $booking->setStatus('CONFIRMED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->cancelBooking($bookingId);
        $this->assertSame($booking, $result);
        $this->assertSame('CANCELLED', $result->getStatus());
    }

    public function testCompleteBooking(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $booking->setStatus('CONFIRMED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->completeBooking($bookingId);
        $this->assertSame($booking, $result);
        $this->assertSame('COMPLETED', $result->getStatus());
    }

    public function testRescheduleBooking(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $newDate = new \DateTime('+1 week');
        $newSlot = '14:00-15:00';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->rescheduleBooking($bookingId, $newDate, $newSlot);
        $this->assertSame($booking, $result);
        $this->assertSame($newDate, $result->getBookingDate());
        $this->assertSame($newSlot, $result->getTimeSlot());
    }

    public function testGetUserUpcomingBookings(): void
    {
        $userId = 'user_123';
        $now = new \DateTime();
        $bookings = [
            new ServiceBooking('id1', $userId, 'biz', 'service', new \DateTime('+1 day'), 'slot'),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('b.userId = :userId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('b.bookingDate >= :now')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($bookings);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getUserUpcomingBookings($userId);
        $this->assertCount(1, $result);
    }

    public function testGetBusinessBookingsInRange(): void
    {
        $businessId = 'biz_123';
        $start = new \DateTime('2025-06-01');
        $end = new \DateTime('2025-06-30');
        $bookings = [
            new ServiceBooking('id1', 'user', $businessId, 'service', new \DateTime(), 'slot'),
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('b.businessId = :businessId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('b.bookingDate BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($bookings);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getBusinessBookingsInRange($businessId, $start, $end);
        $this->assertCount(1, $result);
    }

    public function testGetAvailableTimeSlots(): void
    {
        $businessId = 'biz_123';
        $date = new \DateTime('2025-06-15');
        $existingBookings = [
            new ServiceBooking('id1', 'user', $businessId, 'service', $date, '10:00-11:00'),
        ];
        $allSlots = ['09:00-10:00', '10:00-11:00', '11:00-12:00', '14:00-15:00'];

        // Mock repository to return existing bookings for the date query
        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturnCallback(function ($alias) use ($existingBookings, $allSlots) {
                $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
                $qb->expects($this->once())
                    ->method('where')
                    ->with('b.businessId = :businessId')
                    ->willReturnSelf();
                $qb->expects($this->once())
                    ->method('andWhere')
                    ->with('DATE(b.bookingDate) = :date')
                    ->willReturnSelf();
                $qb->expects($this->exactly(2))
                    ->method('setParameter')
                    ->willReturnSelf();
                $query = $this->createMock(\Doctrine\ORM\Query::class);
                $query->expects($this->once())
                    ->method('getResult')
                    ->willReturn($existingBookings);
                $qb->expects($this->once())
                    ->method('getQuery')
                    ->willReturn($query);
                return $qb;
            });

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        // For available slots, we exclude booked ones
        $result = $this->service->getAvailableTimeSlots($businessId, $date, $allSlots);
        $this->assertEquals(['09:00-10:00', '11:00-12:00', '14:00-15:00'], $result);
    }

    public function testGetBookingsCountByStatus(): void
    {
        $counts = ['PENDING' => 5, 'CONFIRMED' => 3, 'CANCELLED' => 2];

        $rows = [
            ['status' => 'PENDING', 'cnt' => 5],
            ['status' => 'CONFIRMED', 'cnt' => 3],
            ['status' => 'CANCELLED', 'cnt' => 2],
        ];

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('b.status, COUNT(b.id) AS cnt')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('groupBy')
            ->with('b.status')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($rows);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getBookingsCountByStatus();
        $this->assertSame($counts, $result);
    }

    public function testGetRevenueByBusiness(): void
    {
        $businessId = 'biz_123';
        $revenue = 250000; // $2500

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('SUM(b.price)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('b.businessId = :businessId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('b.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $revenue);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ServiceBooking::class)
            ->willReturn($repo);

        $result = $this->service->getRevenueByBusiness($businessId);
        $this->assertSame($revenue, $result);
    }

    public function testGetRevenueByBusinessWithDateRange(): void
    {
        $start = new \DateTime('-1 month');
        $end = new \DateTime();

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('b.businessId = :businessId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('b.bookingDate BETWEEN :start AND :end')
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('100');
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getRevenueByBusiness('biz', $start, $end);
        $this->assertSame(100, $result);
    }

    public function testGetBookingsWithNoShow(): void
    {
        $bookings = [new ServiceBooking('id1', 'user', 'biz', 'service', new \DateTime(), 'slot')];
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('b.status = \'NO_SHOW\'')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($bookings);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getBookingsWithNoShow();
        $this->assertCount(1, $result);
    }

    public function testMarkNoShow(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $booking->setStatus('CONFIRMED');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->markNoShow($bookingId);
        $this->assertSame($booking, $result);
        $this->assertSame('NO_SHOW', $result->getStatus());
    }

    public function testAddReview(): void
    {
        $bookingId = 'booking_123';
        $booking = new ServiceBooking($bookingId, 'user', 'biz', 'service', new \DateTime(), 'slot');
        $review = ['rating' => 5, 'comment' => 'Great!'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($bookingId)
            ->willReturn($booking);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->addReview($bookingId, $review);
        $this->assertSame($booking, $result);
        $this->assertSame($review, $result->getReview());
    }
}
