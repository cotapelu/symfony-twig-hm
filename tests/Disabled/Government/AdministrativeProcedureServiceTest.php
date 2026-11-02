<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\AdministrativeProcedureService;
use App\Entity\Php\AdministrativeProcedure;
use App\Repository\Php\AdministrativeProcedureRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdministrativeProcedureServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private AdministrativeProcedureRepository $repository;
    private AdministrativeProcedureService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(AdministrativeProcedureRepository::class);
        $this->service = new AdministrativeProcedureService($this->em, $this->repository);
    }

    public function testCreateProcedure_Success(): void
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'PROC-001'])
            ->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createAdministrativeProcedure(
            'PROC-001',
            'Building Permit',
            'Desc',
            ['doc1' => 'required']
        );

        $this->assertInstanceOf(AdministrativeProcedure::class, $result);
        $this->assertEquals('PROC-001', $result->getCode());
        $this->assertEquals('Building Permit', $result->getName());
        $this->assertEquals('ACTIVE', $result->getStatus());
    }

    public function testCreateProcedure_DuplicateCode_ThrowsException(): void
    {
        $existing = $this->createMock(AdministrativeProcedure::class);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'EXISTING'])
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Procedure with code 'EXISTING' already exists");

        $this->service->createAdministrativeProcedure('EXISTING', 'Name');
    }

    public function testCreateProcedure_EmptyCode_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createAdministrativeProcedure('', 'Name');
    }

    public function testCreateProcedure_EmptyName_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createAdministrativeProcedure('CODE', '');
    }

    public function testGetProcedure_Exists(): void
    {
        $procedure = new AdministrativeProcedure('id-1', 'PROC-1', 'Name');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($procedure);
        $result = $this->service->getAdministrativeProcedure('id-1');
        $this->assertSame($procedure, $result);
    }

    public function testGetProcedure_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getAdministrativeProcedure('missing');
    }

    public function testGetProcedureByCode_ReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('findOneBy')->with(['code' => 'NOTEXIST'])->willReturn(null);
        $result = $this->service->getProcedureByCode('NOTEXIST');
        $this->assertNull($result);
    }

    public function testGetProcedureByCode_ReturnsProcedure(): void
    {
        $proc = new AdministrativeProcedure('id-1', 'PROC-1', 'Name');
        $this->repository->expects($this->once())->method('findOneBy')->with(['code' => 'PROC-1'])->willReturn($proc);
        $result = $this->service->getProcedureByCode('PROC-1');
        $this->assertSame($proc, $result);
    }

    public function testUpdateProcedure_Success(): void
    {
        $procedure = new AdministrativeProcedure('id-1', 'OLD-CODE', 'Old Name');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($procedure);

        // For code uniqueness check: first call findOneBy returns null (no conflict)
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'NEW-CODE'])
            ->willReturn(null);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateAdministrativeProcedure('id-1', [
            'code' => 'NEW-CODE',
            'name' => 'New Name',
            'status' => 'INACTIVE',
        ]);

        $this->assertEquals('NEW-CODE', $result->getCode());
        $this->assertEquals('New Name', $result->getName());
        $this->assertEquals('INACTIVE', $result->getStatus());
    }

    public function testUpdateProcedure_DuplicateCode_ThrowsException(): void
    {
        $procedure = new AdministrativeProcedure('id-1', 'CODE-1', 'Name');
        $conflicting = new AdministrativeProcedure('id-2', 'CODE-2', 'Other');

        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($procedure);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'CODE-2'])
            ->willReturn($conflicting);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Procedure with code 'CODE-2' already exists");

        $this->service->updateAdministrativeProcedure('id-1', ['code' => 'CODE-2']);
    }

    public function testUpdateProcedure_NotFound_ThrowsException(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateAdministrativeProcedure('missing', ['name' => 'New']);
    }

    public function testDeleteProcedure_Success(): void
    {
        $procedure = new AdministrativeProcedure('id-1', 'CODE', 'Name');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($procedure);
        $this->em->expects($this->once())->method('remove')->with($procedure);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteAdministrativeProcedure('id-1');
    }

    public function testFindByCode_ReturnsArray(): void
    {
        $procedures = [new AdministrativeProcedure('id-1', 'CODE-1', 'Name')];
        $this->repository->expects($this->once())->method('findBy')->with(['code' => 'CODE-1'])->willReturn($procedures);
        $result = $this->service->findByCode('CODE-1');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $procedures = [new AdministrativeProcedure('id-1', 'CODE', 'Name')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'ACTIVE'])->willReturn($procedures);
        $result = $this->service->findByStatus('ACTIVE');
        $this->assertCount(1, $result);
    }
}
