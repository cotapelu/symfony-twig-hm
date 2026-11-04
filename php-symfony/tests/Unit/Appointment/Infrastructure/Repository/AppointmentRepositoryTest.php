<?php declare(strict_types=1);

namespace Tests\Unit\Appointment\Infrastructure\Repository;

use App\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Appointment\Domain\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppointmentRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ClassMetadata $classMetadata;
    private AppointmentRepository $repository;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->classMetadata->name = Appointment::class;
        $this->repository = new AppointmentRepository($this->em, $this->classMetadata);
    }

    private function createAppointmentMock(): MockObject
    {
        return $this->createMock(Appointment::class);
    }

    private function createQueryBuilderMock(): MockObject
    {
        return $this->createMock(QueryBuilder::class);
    }

    private function createQueryMock(): MockObject
    {
        return $this->createMock(Query::class);
    }

    public function testFindById_ReturnsNull_WhenNotFound(): void
    {
        $this->em->expects($this->once())
            ->method('find')
            ->with(Appointment::class, 'nonexistent')
            ->willReturn(null);

        $result = $this->repository->findById('nonexistent');
        $this->assertNull($result);
    }

    public function testFindById_ReturnsAppointment_WhenFound(): void
    {
        $appointment = $this->createAppointmentMock();
        $this->em->expects($this->once())
            ->method('find')
            ->with(Appointment::class, 'appt-123')
            ->willReturn($appointment);

        $result = $this->repository->findById('appt-123');
        $this->assertSame($appointment, $result);
    }

    public function testFindByNumber_ReturnsNull_WhenNotFound(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('a')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->with(Appointment::class, 'a')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('a.appointmentNumber = :number')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('number', 'NUM123')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $result = $this->repository->findByNumber('NUM123');
        $this->assertNull($result);
    }

    public function testFindByNumber_ReturnsAppointment_WhenFound(): void
    {
        $appointment = $this->createAppointmentMock();
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects($this->once())->method('select')->with('a')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Appointment::class, 'a')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('a.appointmentNumber = :number')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('number', 'NUM456')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($appointment);

        $result = $this->repository->findByNumber('NUM456');
        $this->assertSame($appointment, $result);
    }

    public function testFindByPhone_ReturnsArray(): void
    {
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();
        $appointments = [$this->createAppointmentMock()];

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $qb->expects($this->once())->method('select')->with('a')->willReturnSelf();
        $qb->expects($this->once())->method('from')->with(Appointment::class, 'a')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('a.citizenPhone = :phone')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->with('phone', '0123456789')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('a.appointmentDate', 'DESC')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($appointments);

        $result = $this->repository->findByPhone('0123456789');
        $this->assertCount(1, $result);
    }

    public function testSave_PersistsNewEntity(): void
    {
        $appointment = $this->createAppointmentMock();

        $this->em->expects($this->once())
            ->method('contains')
            ->with($appointment)
            ->willReturn(false);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($appointment)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->save($appointment);
        $this->addToAssertionCount(1);
    }

    public function testSave_DoesNotPersistIfAlreadyManaged(): void
    {
        $appointment = $this->createAppointmentMock();

        $this->em->expects($this->once())
            ->method('contains')
            ->with($appointment)
            ->willReturn(true);
        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->save($appointment);
        $this->addToAssertionCount(1);
    }

    public function testRemove(): void
    {
        $appointment = $this->createAppointmentMock();

        $this->em->expects($this->once())
            ->method('remove')
            ->with($appointment)
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->repository->remove($appointment);
        $this->addToAssertionCount(1);
    }

    /**
     * Fluent QueryBuilder stub: every builder call returns the same mock and
     * getQuery() returns a Query whose getResult()/getOneOrNullResult() are
     * configurable per test.
     */
    private function stubQueryBuilder(array $results = [], $scalar = null): QueryBuilder
    {
        $qb = $this->createQueryBuilderMock();
        // One Query object serves both getResult (stats) and getSingleScalarResult
        // (avg rating) — getStatistics runs two queries over the same builder.
        $query = $this->createQueryMock();
        $query->method('getResult')->willReturn($results);
        $query->method('getSingleScalarResult')->willReturn($scalar);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        return $qb;
    }

    public function testFindByWard_NoFilters(): void
    {
        $expected = [$this->createAppointmentMock()];
        $qb = $this->stubQueryBuilder($expected);
        $qb->expects($this->never())->method('setMaxResults');
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->findByWard('W01');
        $this->assertSame($expected, $result);
    }

    public function testFindByWard_WithStatusAndLimit(): void
    {
        $expected = [];
        $qb = $this->stubQueryBuilder($expected);
        $qb->expects($this->once())->method('andWhere')->with('a.status = :status')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->with(5)->willReturnSelf();
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame([], $this->repository->findByWard('W01', Appointment::STATUS_CONFIRMED, 5));
    }

    public function testFindByOfficer_NoStatus(): void
    {
        $expected = [$this->createAppointmentMock()];
        $qb = $this->stubQueryBuilder($expected);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame($expected, $this->repository->findByOfficer('OFF1'));
    }

    public function testFindByOfficer_WithStatus(): void
    {
        $qb = $this->stubQueryBuilder([]);
        $qb->expects($this->once())->method('andWhere')->with('a.status = :status')->willReturnSelf();
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame([], $this->repository->findByOfficer('OFF1', Appointment::STATUS_PENDING));
    }

    public function testFindUpcomingAppointments_AllOfficers(): void
    {
        $expected = [$this->createAppointmentMock()];
        $qb = $this->stubQueryBuilder($expected);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame($expected, $this->repository->findUpcomingAppointments());
    }

    public function testFindUpcomingAppointments_FilteredByOfficer(): void
    {
        $qb = $this->stubQueryBuilder([]);
        // status IN + officerId
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame([], $this->repository->findUpcomingAppointments('OFF9'));
    }

    public function testFindTodayAppointments_AllOfficers(): void
    {
        $expected = [$this->createAppointmentMock()];
        $qb = $this->stubQueryBuilder($expected);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame($expected, $this->repository->findTodayAppointments('W01'));
    }

    public function testFindTodayAppointments_FilteredByOfficer(): void
    {
        $qb = $this->stubQueryBuilder([]);
        // appointmentDate = :today + officerId
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnSelf();
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertSame([], $this->repository->findTodayAppointments('W01', 'OFF9'));
    }

    // ================= findAvailableSlots (pure slot/overlap logic) =================
    private function occupiedAppointment(string $start, string $end): MockObject
    {
        $a = $this->createAppointmentMock();
        $a->method('getAppointmentTime')->willReturn($start);
        $a->method('getEndTime')->willReturn(new \DateTimeImmutable($end));
        return $a;
    }

    public function testFindAvailableSlots_AllFreeWhenNoneOccupied(): void
    {
        $qb = $this->stubQueryBuilder([]);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $slots = $this->repository->findAvailableSlots('W01', '2026-05-01');
        $this->assertCount(16, $slots);
        $this->assertContains('08:00', $slots);
    }

    public function testFindAvailableSlots_ExcludesOverlappingSlot(): void
    {
        // Occupied 08:00-08:30 blocks the 08:00 slot but NOT 08:30
        $qb = $this->stubQueryBuilder([$this->occupiedAppointment('08:00', '08:30')]);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $slots = $this->repository->findAvailableSlots('W01', '2026-05-01');
        $this->assertNotContains('08:00', $slots);
        $this->assertContains('08:30', $slots);
        $this->assertCount(15, $slots);
    }

    public function testFindAvailableSlots_ExactMatchBlocksSingleSlot(): void
    {
        // Occupied 09:00-10:00 blocks exactly the 09:00 slot
        $qb = $this->stubQueryBuilder([$this->occupiedAppointment('09:00', '10:00')]);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $slots = $this->repository->findAvailableSlots('W01', '2026-05-01');
        $this->assertNotContains('09:00', $slots);
        $this->assertContains('08:30', $slots);
        $this->assertContains('10:00', $slots);
    }

    public function testFindAvailableSlots_MultipleOccupied(): void
    {
        $qb = $this->stubQueryBuilder([
            $this->occupiedAppointment('08:00', '08:30'),
            $this->occupiedAppointment('14:00', '15:00'),
        ]);
        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $slots = $this->repository->findAvailableSlots('W01', '2026-05-01');
        $this->assertNotContains('08:00', $slots);
        $this->assertNotContains('14:00', $slots);
        $this->assertContains('13:30', $slots);
    }

    // ================= getStatistics =================
    public function testGetStatistics_Empty(): void
    {
        $qb = $this->stubQueryBuilder([], null);
        $this->em->method('createQueryBuilder')->willReturn($qb, $qb);

        $stats = $this->repository->getStatistics('W01');
        $this->assertSame(0, $stats['total']);
        $this->assertNull($stats['averageRating']);
        $this->assertSame(0, $stats['pending']);
    }

    public function testGetStatistics_AggregatesStatusCounts(): void
    {
        $rows = [
            ['status' => Appointment::STATUS_PENDING, 'count' => 3],
            ['status' => Appointment::STATUS_COMPLETED, 'count' => 2],
            ['status' => 'unknown_status', 'count' => 1], // ignored branch
        ];
        $qb = $this->stubQueryBuilder($rows, '4.25');
        $this->em->method('createQueryBuilder')->willReturn($qb, $qb);

        $stats = $this->repository->getStatistics('W01');
        $this->assertSame(6, $stats['total']);
        $this->assertSame(3, $stats['pending']);
        $this->assertSame(2, $stats['completed']);
        $this->assertEqualsWithDelta(4.25, $stats['averageRating'], 0.001);
    }

    public function testGetStatistics_WithDateRange(): void
    {
        $qb = $this->stubQueryBuilder([['status' => Appointment::STATUS_CONFIRMED, 'count' => 1]], '5');
        // stats query: 2 date bounds; avg query: rating-not-null + 2 date bounds
        $qb->expects($this->exactly(5))->method('andWhere');
        $this->em->method('createQueryBuilder')->willReturn($qb, $qb);

        $stats = $this->repository->getStatistics(
            'W01',
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-12-31')
        );
        $this->assertSame(1, $stats['confirmed']);
        $this->assertEqualsWithDelta(5.0, $stats['averageRating'], 0.001);
    }
}
