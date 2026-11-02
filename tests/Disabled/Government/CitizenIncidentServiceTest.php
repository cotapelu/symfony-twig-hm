<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\CitizenIncidentService;
use App\Entity\Php\CitizenIncident;
use App\Repository\Php\CitizenIncidentRepository;
use Doctrine\ORM\EntityManagerInterface;

class CitizenIncidentServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private CitizenIncidentRepository $repository;
    private CitizenIncidentService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CitizenIncidentRepository::class);
        $this->service = new CitizenIncidentService($this->em, $this->repository);
    }

    public function testCreateIncident_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createIncident(
            'citizen-123',
            'COMPLAINT',
            ['details' => 'Noise complaint'],
            'RESOLVED'
        );

        $this->assertInstanceOf(CitizenIncident::class, $result);
        $this->assertEquals('citizen-123', $result->getCitizenId());
        $this->assertEquals('COMPLAINT', $result->getType());
        $this->assertEquals('RESOLVED', $result->getStatus());
    }

    public function testCreateIncident_EmptyFields_ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createIncident('', 'TYPE');
    }

    public function testGetIncident_Exists(): void
    {
        $incident = new CitizenIncident('id-1', 'citizen-1', 'TYPE');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($incident);
        $result = $this->service->getIncident('id-1');
        $this->assertSame($incident, $result);
    }

    public function testGetIncident_NotFound_ThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getIncident('missing');
    }

    public function testUpdateIncident_Success(): void
    {
        $incident = new CitizenIncident('id-1', 'citizen-1', 'TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($incident);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateIncident('id-1', [
            'type' => 'NEW-TYPE',
            'status' => 'CLOSED',
        ]);

        $this->assertEquals('NEW-TYPE', $result->getType());
        $this->assertEquals('CLOSED', $result->getStatus());
    }

    public function testUpdateIncident_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateIncident('missing', ['type' => 'New']);
    }

    public function testDeleteIncident_Success(): void
    {
        $incident = new CitizenIncident('id-1', 'citizen-1', 'TYPE');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($incident);
        $this->em->expects($this->once())->method('remove')->with($incident);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteIncident('id-1');
    }

    public function testFindByCitizenId_ReturnsArray(): void
    {
        $incidents = [new CitizenIncident('id-1', 'citizen-123', 'TYPE')];
        $this->repository->expects($this->once())->method('findBy')->with(['citizenId' => 'citizen-123'])->willReturn($incidents);
        $result = $this->service->findByCitizenId('citizen-123');
        $this->assertCount(1, $result);
    }

    public function testFindByType_ReturnsArray(): void
    {
        $incidents = [new CitizenIncident('id-1', 'citizen-1', 'COMPLAINT')];
        $this->repository->expects($this->once())->method('findBy')->with(['type' => 'COMPLAINT'])->willReturn($incidents);
        $result = $this->service->findByType('COMPLAINT');
        $this->assertCount(1, $result);
    }

    public function testFindByStatus_ReturnsArray(): void
    {
        $incidents = [new CitizenIncident('id-1', 'citizen-1', 'TYPE')];
        $this->repository->expects($this->once())->method('findBy')->with(['status' => 'PENDING'])->willReturn($incidents);
        $result = $this->service->findByStatus('PENDING');
        $this->assertCount(1, $result);
    }

    public function testUpdate_OnlyData(): void
    {
        $incident = new CitizenIncident('id-1', 'citizen-1', 'TYPE');
        $this->repository->expects($this->once())->method('find')->willReturn($incident);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateIncident('id-1', ['data' => ['new' => 'info']]);

        $this->assertEquals(['new' => 'info'], $result->getData());
    }
}
