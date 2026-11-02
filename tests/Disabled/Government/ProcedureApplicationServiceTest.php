<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\ProcedureApplicationService;
use App\Entity\Php\ProcedureApplication;
use App\Repository\Php\ProcedureApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProcedureApplicationServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProcedureApplicationRepository $repository;
    private ProcedureApplicationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ProcedureApplicationRepository::class);
        $this->service = new ProcedureApplicationService($this->em, $this->repository);
    }

    public function testCreateApplication_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createApplication(
            'citizen-123',
            'proc-456',
            ['field' => 'value'],
            'APPROVED'
        );

        $this->assertInstanceOf(ProcedureApplication::class, $result);
        $this->assertEquals('citizen-123', $result->getCitizenId());
        $this->assertEquals('proc-456', $result->getProcedureId());
        $this->assertEquals('APPROVED', $result->getStatus());
    }

    public function testCreateApplication_EmptyFields_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createApplication('', 'proc');
    }

    public function testGetApplication_Exists(): void
    {
        $app = new ProcedureApplication('id-1', 'citizen-1', 'proc-1');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($app);
        $result = $this->service->getApplication('id-1');
        $this->assertSame($app, $result);
    }

    public function testGetApplication_NotFound_Throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getApplication('missing');
    }

    public function testUpdateApplication_Success(): void
    {
        $app = new ProcedureApplication('id-1', 'citizen-1', 'proc-1');
        $this->repository->expects($this->once())->method('find')->willReturn($app);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateApplication('id-1', [
            'status' => 'REJECTED',
            'data' => ['reason' => 'Missing docs'],
        ]);

        $this->assertEquals('REJECTED', $result->getStatus());
        $this->assertEquals(['reason' => 'Missing docs'], $result->getData());
    }

    public function testUpdateApplication_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateApplication('missing', ['status' => 'NEW']);
    }

    public function testDeleteApplication_Success(): void
    {
        $app = new ProcedureApplication('id-1', 'citizen-1', 'proc-1');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($app);
        $this->em->expects($this->once())->method('remove')->with($app);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteApplication('id-1');
    }

    public function testFindByCitizenId_ReturnsArray(): void
    {
        $apps = [new ProcedureApplication('id-1', 'citizen-123', 'proc-1')];
        $this->repository->expects($this->once())->method('findBy')->with(['citizenId' => 'citizen-123'])->willReturn($apps);
        $result = $this->service->findByCitizenId('citizen-123');
        $this->assertCount(1, $result);
    }

    public function testFindByProcedureId_ReturnsArray(): void
    {
        $apps = [new ProcedureApplication('id-1', 'citizen-1', 'proc-123')];
        $this->repository->expects($this->once())->method('findBy')->with(['procedureId' => 'proc-123'])->willReturn($apps);
        $result = $this->service->findByProcedureId('proc-123');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $apps = [new ProcedureApplication('id-1', 'citizen-1', 'proc-1')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'PENDING'])->willReturn($apps);
        $result = $this->service->findByStatus('PENDING');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyData(): void
    {
        $app = new ProcedureApplication('id-1', 'citizen-1', 'proc-1');
        $this->repository->expects($this->once())->method('find')->willReturn($app);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateApplication('id-1', ['data' => ['new' => 'info']]);

        $this->assertEquals(['new' => 'info'], $result->getData());
    }
}
