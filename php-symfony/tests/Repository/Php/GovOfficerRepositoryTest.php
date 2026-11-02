<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\GovOfficerRepository;
use App\Entity\Php\GovOfficer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GovOfficerRepositoryTest extends TestCase
{
    private GovOfficerRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(GovOfficerRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findBy', 'findOneBy', 'count', 'getEntityManager'])
            ->getMock();

        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testSavePersistsAndFlushes(): void
    {
        $officer = $this->createMock(GovOfficer::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($officer);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->save($officer);
    }

    public function testRemoveRemovesAndFlushes(): void
    {
        $officer = $this->createMock(GovOfficer::class);

        $this->repo->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($officer);
        $this->em->expects($this->once())
            ->method('flush');

        $this->repo->remove($officer);
    }

    public function testFindById(): void
    {
        $id = 'officer-123';
        $expected = $this->createMock(GovOfficer::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expected);

        $result = $this->repo->findById($id);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = $this->createMock(GovOfficer::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => $userId])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByDepartment(): void
    {
        $department = 'Police';
        $expected = [$this->createMock(GovOfficer::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['department' => $department])
            ->willReturn($expected);

        $result = $this->repo->findByDepartment($department);
        $this->assertSame($expected, $result);
    }

    public function testFindByPosition(): void
    {
        $position = 'Inspector';
        $expected = [$this->createMock(GovOfficer::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['position' => $position])
            ->willReturn($expected);

        $result = $this->repo->findByPosition($position);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $expected = [$this->createMock(GovOfficer::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveOfficers(): void
    {
        $expected = [$this->createMock(GovOfficer::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveOfficers();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveOfficers(): void
    {
        $expected = [$this->createMock(GovOfficer::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveOfficers();
        $this->assertSame($expected, $result);
    }

    public function testCountActive(): void
    {
        $count = 5;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($count);

        $result = $this->repo->countActive();
        $this->assertSame($count, $result);
    }

    public function testCountByDepartment(): void
    {
        $department = 'Fire';
        $count = 12;

        $this->repo->expects($this->once())
            ->method('count')
            ->with(['department' => $department])
            ->willReturn($count);

        $result = $this->repo->countByDepartment($department);
        $this->assertSame($count, $result);
    }
}
