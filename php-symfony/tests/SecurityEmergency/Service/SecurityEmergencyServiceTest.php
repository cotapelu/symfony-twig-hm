<?php

declare(strict_types=1);

namespace App\Tests\SecurityEmergency\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\SecurityEmergency\Entity\SecurityIncident;
use App\SecurityEmergency\Repository\SecurityIncidentRepository;
use App\SecurityEmergency\Service\SecurityEmergencyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class SecurityEmergencyServiceTest extends TestCase
{
    private MockObject $incidentRepository;
    private MockObject $entityManager;
    private MockObject $logger;

    private SecurityEmergencyService $service;

    protected function setUp(): void
    {
        $this->incidentRepository = $this->createMock(SecurityIncidentRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new SecurityEmergencyService(
            $this->incidentRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testCreateIncidentSavesToRepository(): void
    {
        $type = 'Trộm cắp';
        $reporterId = 'user-123';
        $location = 'Khu vực chợ';
        $description = 'Mất xe máy';
        $status = 'Đang điều tra';

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityIncident $i) use ($type, $reporterId, $location, $description, $status) {
                return $i->getType() === $type
                    && $i->getReporterId() === $reporterId
                    && $i->getLocation() === $location
                    && $i->getDescription() === $description
                    && $i->getStatus() === $status;
            }));

        $result = $this->service->createIncident($type, $reporterId, $location, $description, $status);
        $this->assertInstanceOf(SecurityIncident::class, $result);
    }

    public function testCreateIncidentValidatesType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid incident type');
        $this->service->createIncident('Invalid Type', 'user-1');
    }

    public function testCreateIncidentValidatesStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid incident status');
        $this->service->createIncident('Trộm cắp', 'user-1', null, null, 'Invalid');
    }

    public function testGetIncidentReturnsFromRepository(): void
    {
        $id = 'incident-123';
        $expected = new SecurityIncident('Trộm cắp', 'Đang điều tra', 'user-1');

        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($expected);

        $result = $this->service->getIncident($id);
        $this->assertSame($expected, $result);
    }

    public function testGetIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->service->getIncident('notfound');
        $this->assertNull($result);
    }

    public function testUpdateIncidentStatusChangesAndSaves(): void
    {
        $id = 'incident-123';
        $incident = $this->createMock(SecurityIncident::class);
        $incident->method('getId')->willReturn($id);
        $incident->expects($this->once())->method('setStatus')->with('Đã xử lý');
        $incident->expects($this->once())->method('setUpdatedAt')->with($this->isInstanceOf(\DateTime::class));

        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident);

        $result = $this->service->updateIncidentStatus($id, 'Đã xử lý');
        $this->assertSame($incident, $result);
    }

    public function testUpdateIncidentStatusReturnsNullIfNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->service->updateIncidentStatus('notfound', 'Đã xử lý');
        $this->assertNull($result);
    }

    public function testDeleteIncidentRemovesFromRepository(): void
    {
        $id = 'incident-123';
        $incident = $this->createMock(SecurityIncident::class);

        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($incident);
        $this->incidentRepository->expects($this->once())
            ->method('delete')
            ->with($incident);

        $result = $this->service->deleteIncident($id);
        $this->assertTrue($result);
    }

    public function testDeleteIncidentReturnsFalseIfNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('findById')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->service->deleteIncident('notfound');
        $this->assertFalse($result);
    }

    public function testGetAllIncidentsDelegatesToRepository(): void
    {
        $incidents = [$this->createMock(SecurityIncident::class)];

        $this->incidentRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 50, 0)
            ->willReturn($incidents);

        $result = $this->service->getAllIncidents(50, 0);
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByStatusDelegatesToRepository(): void
    {
        $status = 'Đã xử lý';
        $incidents = [$this->createMock(SecurityIncident::class)];

        $this->incidentRepository->expects($this->once())
            ->method('findAllByStatus')
            ->with($status, 20, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByStatus($status, 20, 0);
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByTypeDelegatesToRepository(): void
    {
        $type = 'Trộm cắp';
        $incidents = [$this->createMock(SecurityIncident::class)];

        $this->incidentRepository->expects($this->once())
            ->method('findAllByType')
            ->with($type, 20, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByType($type, 20, 0);
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByReporterDelegatesToRepository(): void
    {
        $reporterId = 'user-123';
        $incidents = [$this->createMock(SecurityIncident::class)];

        $this->incidentRepository->expects($this->once())
            ->method('findByReporter')
            ->with($reporterId, 20, 0)
            ->willReturn($incidents);

        $result = $this->service->getIncidentsByReporter($reporterId, 20, 0);
        $this->assertSame($incidents, $result);
    }
}
