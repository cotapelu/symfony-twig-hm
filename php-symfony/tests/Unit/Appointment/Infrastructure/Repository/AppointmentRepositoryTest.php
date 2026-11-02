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
}
