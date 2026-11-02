<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Application\Service\IncidentService;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use App\ANTT\Application\DTO\CreateIncidentRequest;
use App\ANTT\Application\DTO\UpdateIncidentRequest;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IncidentServiceTest extends TestCase
{
    private IncidentRepositoryInterface&MockObject $incidentRepository;
    private LoggerInterface&MockObject $logger;
    private IncidentService $service;

    protected function setUp(): void
    {
        $this->incidentRepository = $this->createMock(IncidentRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new IncidentService($this->incidentRepository, $this->logger);
    }

    public function testCreateIncident(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'Test Incident';
        $request->description = 'Description';
        $request->incidentType = 'crime';
        $request->severity = 'high';
        $request->wardCode = 'W001';
        $request->locationAddress = '123 Street';
        $request->latitude = 10.0;
        $request->longitude = 106.0;
        $request->reporterName = 'John Doe';
        $request->reporterPhone = '+84912345678';
        $request->reporterAnonymous = false;

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $incident) use ($request) {
                return $incident->getTitle() === $request->title
                    && $incident->getDescription() === $request->description
                    && $incident->getIncidentType() === $request->incidentType
                    && $incident->getSeverity() === $request->severity
                    && $incident->getWardCode() === $request->wardCode
                    && $incident->getLocationAddress() === $request->locationAddress
                    && $incident->getLatitude() === $request->latitude
                    && $incident->getLongitude() === $request->longitude
                    && $incident->getReporterName() === $request->reporterName
                    && $incident->getReporterPhone() === $request->reporterPhone
                    && $incident->isReporterAnonymous() === $request->reporterAnonymous;
            }))
            ->willReturnCallback(function (Incident $incident) {
                // Simulate setting an ID
                $ref = new \ReflectionProperty(Incident::class, 'id');
                $ref->setAccessible(true);
                $ref->setValue($incident, 123);
                return $incident;
            });

        $result = $this->service->createIncident($request, 'user-123');

        $this->assertInstanceOf(Incident::class, $result);
        $this->assertSame(123, $result->getId());
        $this->assertSame('Test Incident', $result->getTitle());
    }

    public function testCreateIncidentValidationThrowsForBlankTitle(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = '';
        $request->incidentType = 'crime';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title');

        $this->service->createIncident($request, 'user-123');
    }

    public function testCreateIncidentValidationThrowsForBlankIncidentType(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'Test';
        $request->incidentType = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incident type');

        $this->service->createIncident($request, 'user-123');
    }

    public function testCreateIncidentValidatesLatitude(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'Test';
        $request->incidentType = 'crime';
        $request->latitude = 100; // invalid

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude');

        $this->service->createIncident($request, 'user-123');
    }

    public function testUpdateIncidentWhenExists(): void
    {
        $id = 123;
        $incident = new Incident();
        // Set required fields via reflection or setters? Incident entity has setters for most.
        $refId = new \ReflectionProperty(Incident::class, 'id');
        $refId->setAccessible(true);
        $refId->setValue($incident, $id);

        $incident->setTitle('Old Title');
        $incident->setSeverity('low');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($incident);
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident)
            ->willReturnArgument(0);

        $update = new UpdateIncidentRequest();
        $update->title = 'New Title';
        $update->severity = 'high';

        $result = $this->service->updateIncident($id, $update);

        $this->assertSame($incident, $result);
        $this->assertSame('New Title', $incident->getTitle());
        $this->assertSame('high', $incident->getSeverity());
    }

    public function testUpdateIncidentWhenNotFound(): void
    {
        $id = 999;
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $update = new UpdateIncidentRequest();
        $update->title = 'New';

        $result = $this->service->updateIncident($id, $update);

        $this->assertNull($result);
    }

    public function testGetIncident(): void
    {
        $id = 123;
        $incident = new Incident();
        $ref = new \ReflectionProperty(Incident::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($incident, $id);

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($incident);

        $result = $this->service->getIncident($id);

        $this->assertSame($incident, $result);
    }

    public function testAssignIncident(): void
    {
        $id = 123;
        $incident = new Incident();
        $ref = new \ReflectionProperty(Incident::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($incident, $id);

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($incident);
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident)
            ->willReturnArgument(0);

        $result = $this->service->assignIncident($id, 'officer-456', 'team-abc');

        $this->assertSame($incident, $result);
        $this->assertSame('officer-456', $incident->getAssignedOfficerId());
        $this->assertSame('team-abc', $incident->getAssignedTeam());
    }

    public function testResolveIncident(): void
    {
        $id = 123;
        $incident = new Incident();
        $ref = new \ReflectionProperty(Incident::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($incident, $id);

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($incident);
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($incident)
            ->willReturnArgument(0);

        $result = $this->service->resolveIncident($id, 'Resolved with action');

        $this->assertSame($incident, $result);
        $this->assertNotNull($result->getResolvedAt());
        $this->assertSame('resolved', $result->getStatus());
        $this->assertSame('Resolved with action', $result->getResolutionSummary());
    }
}
