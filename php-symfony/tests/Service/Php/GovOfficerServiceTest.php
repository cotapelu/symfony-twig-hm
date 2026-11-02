<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\GovOfficer;
use App\Repository\Php\GovOfficerRepository;
use App\Service\Php\GovOfficerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GovOfficerServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GovOfficerRepository&MockObject $repository;
    private GovOfficerService $service;

    private string $officerId;
    private GovOfficer $officer;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(GovOfficerRepository::class);
        $this->service = new GovOfficerService($this->em, $this->repository);

        $this->officerId = bin2hex(random_bytes(16));
        $this->officer = new GovOfficer(
            $this->officerId,
            'user-1',
            'Department A',
            'Position X'
        );
    }

    public function testCreateGovOfficerSuccess(): void
    {
        $userId = 'user-123';
        $department = 'Health';
        $position = 'Director';
        $metadata = ['level' => 5];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (GovOfficer $o) use (&$captured, $userId, $department, $position, $metadata) {
                $captured = $o;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createGovOfficer($userId, $department, $position, $metadata);
        $this->assertInstanceOf(GovOfficer::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($department, $result->getDepartment());
        $this->assertSame($position, $result->getPosition());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateGovOfficerValidationEmptyUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');
        $this->service->createGovOfficer('', 'Dept', 'Pos');
    }

    public function testCreateGovOfficerValidationEmptyDepartment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department is required');
        $this->service->createGovOfficer('user', '', 'Pos');
    }

    public function testCreateGovOfficerValidationEmptyPosition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Position is required');
        $this->service->createGovOfficer('user', 'dept', '');
    }

    public function testGetGovOfficerSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);

        $result = $this->service->getGovOfficer($this->officerId);
        $this->assertSame($this->officer, $result);
    }

    public function testGetGovOfficerNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GovOfficer not found');
        $this->service->getGovOfficer($this->officerId);
    }

    public function testUpdateGovOfficerSuccess(): void
    {
        $newDept = 'Education';
        $newPos = 'Principal';
        $newStatus = 'ACTIVE';
        $newMetadata = ['school' => 'High School'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'department' => $newDept,
            'position' => $newPos,
            'status' => $newStatus,
            'metadata' => $newMetadata,
        ];
        $result = $this->service->updateGovOfficer($this->officerId, $data);
        $this->assertSame($this->officer, $result);
        $this->assertSame($newDept, $result->getDepartment());
        $this->assertSame($newPos, $result->getPosition());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateGovOfficerValidationEmptyDepartment(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Department cannot be empty');
        $this->service->updateGovOfficer($this->officerId, ['department' => '   ']);
    }

    public function testUpdateGovOfficerValidationEmptyPosition(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Position cannot be empty');
        $this->service->updateGovOfficer($this->officerId, ['position' => '']);
    }

    public function testUpdateGovOfficerValidationEmptyStatus(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->updateGovOfficer($this->officerId, ['status' => ' ']);
    }

    public function testUpdateGovOfficerNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateGovOfficer($this->officerId, []);
    }

    public function testDeleteGovOfficerSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn($this->officer);
        $this->em->expects($this->once())->method('remove')->with($this->officer);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteGovOfficer($this->officerId);
    }

    public function testDeleteGovOfficerNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->officerId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteGovOfficer($this->officerId);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $results = [$this->officer];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId])
            ->willReturn($results);

        $result = $this->service->findByUserId($userId);
        $this->assertSame($results, $result);
    }

    public function testFindByDepartment(): void
    {
        $department = 'Health';
        $results = [$this->officer];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['department' => $department])
            ->willReturn($results);

        $result = $this->service->findByDepartment($department);
        $this->assertSame($results, $result);
    }
}
