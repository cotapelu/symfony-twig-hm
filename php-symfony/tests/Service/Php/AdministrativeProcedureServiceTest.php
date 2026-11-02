<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\AdministrativeProcedure;
use App\Repository\Php\AdministrativeProcedureRepository;
use App\Service\Php\AdministrativeProcedureService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AdministrativeProcedureRepository&MockObject $repository;
    private AdministrativeProcedureService $service;

    private string $procedureId;
    private AdministrativeProcedure $procedure;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(AdministrativeProcedureRepository::class);
        $this->service = new AdministrativeProcedureService($this->em, $this->repository);

        $this->procedureId = bin2hex(random_bytes(16));
        $this->procedure = new AdministrativeProcedure(
            $this->procedureId,
            'PROC-001',
            'Procedure A'
        );
    }

    public function testCreateAdministrativeProcedureSuccess(): void
    {
        $code = 'NEW-PROC';
        $name = 'New Process';
        $description = 'Description text';
        $requirements = ['doc1', 'doc2'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AdministrativeProcedure $p) use (&$captured, $code, $name, $description, $requirements) {
                $captured = $p;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createAdministrativeProcedure($code, $name, $description, $requirements);
        $this->assertInstanceOf(AdministrativeProcedure::class, $result);
        $this->assertSame($code, $result->getCode());
        $this->assertSame($name, $result->getName());
        $this->assertSame($description, $result->getDescription());
        $this->assertSame($requirements, $result->getRequirements());
        $this->assertSame($captured, $result);
    }

    public function testCreateAdministrativeProcedureValidationEmptyCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code is required');
        $this->service->createAdministrativeProcedure('', 'Name');
    }

    public function testCreateAdministrativeProcedureValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->createAdministrativeProcedure('CODE', '   ');
    }

    public function testCreateAdministrativeProcedureDuplicateCode(): void
    {
        $code = 'EXISTING';
        $existing = $this->createMock(AdministrativeProcedure::class);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Procedure with code '$code' already exists");
        $this->service->createAdministrativeProcedure($code, 'Name');
    }

    public function testGetAdministrativeProcedureSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);

        $result = $this->service->getAdministrativeProcedure($this->procedureId);
        $this->assertSame($this->procedure, $result);
    }

    public function testGetAdministrativeProcedureNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AdministrativeProcedure not found');
        $this->service->getAdministrativeProcedure($this->procedureId);
    }

    public function testGetProcedureByCode(): void
    {
        $code = 'PROC-001';
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($this->procedure);

        $result = $this->service->getProcedureByCode($code);
        $this->assertSame($this->procedure, $result);
    }

    public function testUpdateAdministrativeProcedureSuccess(): void
    {
        $newCode = 'UPDATED-CODE';
        $newName = 'Updated Procedure';
        $newDescription = 'New desc';
        $newRequirements = ['req1'];
        $newStatus = 'ACTIVE';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);
        // For code uniqueness check: findOneBy(newCode) should return null or self but we test scenario where no conflict
        $this->repository->expects($this->any())
            ->method('findOneBy')
            ->with(['code' => $newCode])
            ->willReturn(null);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'code' => $newCode,
            'name' => $newName,
            'description' => $newDescription,
            'requirements' => $newRequirements,
            'status' => $newStatus,
        ];
        $result = $this->service->updateAdministrativeProcedure($this->procedureId, $data);
        $this->assertSame($this->procedure, $result);
        $this->assertSame($newCode, $result->getCode());
        $this->assertSame($newName, $result->getName());
        $this->assertSame($newDescription, $result->getDescription());
        $this->assertSame($newRequirements, $result->getRequirements());
        $this->assertSame($newStatus, $result->getStatus());
    }

    public function testUpdateAdministrativeProcedureValidationEmptyCode(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code cannot be empty');
        $this->service->updateAdministrativeProcedure($this->procedureId, ['code' => '']);
    }

    public function testUpdateAdministrativeProcedureValidationEmptyName(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');
        $this->service->updateAdministrativeProcedure($this->procedureId, ['name' => '   ']);
    }

    public function testUpdateAdministrativeProcedureDuplicateCode(): void
    {
        $newCode = 'OTHER-CODE';
        $other = $this->createMock(AdministrativeProcedure::class);
        $other->method('getId')->willReturn('other-id');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $newCode])
            ->willReturn($other);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Procedure with code '$newCode' already exists");
        $this->service->updateAdministrativeProcedure($this->procedureId, ['code' => $newCode]);
    }

    public function testUpdateAdministrativeProcedureNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateAdministrativeProcedure($this->procedureId, []);
    }

    public function testDeleteAdministrativeProcedureSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn($this->procedure);
        $this->em->expects($this->once())->method('remove')->with($this->procedure);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteAdministrativeProcedure($this->procedureId);
    }

    public function testDeleteAdministrativeProcedureNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->procedureId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteAdministrativeProcedure($this->procedureId);
    }

    public function testFindByCode(): void
    {
        $code = 'PROC-123';
        $results = [$this->procedure];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['code' => $code])
            ->willReturn($results);

        $result = $this->service->findByCode($code);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'ACTIVE';
        $results = [$this->procedure];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
