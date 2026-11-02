<?php

declare(strict_types=1);

namespace App\Tests\Modules\ANTT\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Application\DTO\CreateIncidentRequest;
use App\ANTT\Application\DTO\UpdateIncidentRequest;
use App\ANTT\Application\Service\IncidentService;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IncidentServiceTest extends TestCase
{
    private IncidentRepositoryInterface&MockObject $incidentRepository;
    private IncidentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->incidentRepository = $this->createMock(IncidentRepositoryInterface::class);
        $this->service = new IncidentService($this->incidentRepository);
    }

    private function createValidCreateRequest(array $overrides = []): CreateIncidentRequest
    {
        $request = new CreateIncidentRequest();
        $request->title = $overrides['title'] ?? 'Test Incident';
        $request->description = $overrides['description'] ?? 'Test description';
        $request->incidentType = $overrides['incidentType'] ?? 'crime';
        $request->severity = $overrides['severity'] ?? 'medium';
        $request->wardCode = $overrides['wardCode'] ?? 'W001';
        $request->locationAddress = $overrides['locationAddress'] ?? '123 Main St';
        $request->latitude = $overrides['latitude'] ?? 10.0;
        $request->longitude = $overrides['longitude'] ?? 106.0;
        $request->reporterName = $overrides['reporterName'] ?? 'John Doe';
        $request->reporterPhone = $overrides['reporterPhone'] ?? '0123456789';
        $request->reporterAnonymous = $overrides['reporterAnonymous'] ?? false;

        return $request;
    }

    public function testCreateIncidentSavesIncidentWithCorrectData(): void
    {
        $request = $this->createValidCreateRequest();
        $reporterId = 'user-123';

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $incident) use ($request, $reporterId) {
                return $incident->getTitle() === $request->title
                    && $incident->getDescription() === $request->description
                    && $incident->getIncidentType() === $request->incidentType
                    && $incident->getSeverity() === $request->severity
                    && $incident->getWardCode() === $request->wardCode
                    && $incident->getReporterId() === $reporterId
                    && $incident->getReporterName() === $request->reporterName
                    && $incident->getReporterPhone() === $request->reporterPhone;
            }));

        $this->service->createIncident($request, $reporterId);
    }

    public function testCreateIncidentHandlesAnonymousReporter(): void
    {
        $request = $this->createValidCreateRequest([
            'reporterAnonymous' => true,
            'reporterName' => null,
            'reporterPhone' => null
        ]);
        $reporterId = 'user-123';

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $incident) {
                return $incident->getReporterName() === null
                    && $incident->getReporterPhone() === null;
            }));

        $this->service->createIncident($request, $reporterId);
    }

    public function testUpdateIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = new UpdateIncidentRequest();
        $request->title = 'Updated Title';

        $result = $this->service->updateIncident(999, $request);

        $this->assertNull($result);
    }

    public function testUpdateIncidentUpdatesProvidedFields(): void
    {
        $incident = new Incident();
        $incident->setTitle('Original');
        $incident->setDescription('Original desc');
        $incident->setIncidentType('crime');
        $incident->setSeverity('low');
        $incident->setStatus('open');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getTitle() === 'Updated Title'
                    && $inc->getDescription() === 'Updated desc'
                    && $inc->getSeverity() === 'high'
                    && $inc->getStatus() === 'closed';
            }));

        $request = new UpdateIncidentRequest();
        $request->title = 'Updated Title';
        $request->description = 'Updated desc';
        $request->severity = 'high';
        $request->status = 'closed';

        $result = $this->service->updateIncident(1, $request);

        $this->assertInstanceOf(Incident::class, $result);
    }

    public function testAssignIncidentUpdatesAssignment(): void
    {
        $incident = new Incident();
        $incident->setAssignedOfficerId(null);
        $incident->setAssignedTeam(null);

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getAssignedOfficerId() === 'officer-123'
                    && $inc->getAssignedTeam() === 'team-alpha';
            }));

        $result = $this->service->assignIncident(1, 'officer-123', 'team-alpha');

        $this->assertInstanceOf(Incident::class, $result);
    }

    public function testValidateIdThrowsForInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be a positive integer');

        // Use reflection to call private method
        $method = new \ReflectionMethod(IncidentService::class, 'validateId');
        $method->setAccessible(true);
        $method->invoke($this->service, 0);
    }

    public function testValidateStringNotEmptyThrowsForEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer ID must not be empty');

        // Use reflection to call private method
        $method = new \ReflectionMethod(IncidentService::class, 'validateStringNotEmpty');
        $method->setAccessible(true);
        $method->invoke($this->service, '', 'Officer ID');
    }

    // ==================== RESOLVE INCIDENT TESTS ====================

    public function testResolveIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->resolveIncident(999, 'Issue resolved');

        $this->assertNull($result);
    }

    public function testResolveIncidentSuccessfullyResolves(): void
    {
        $incident = new Incident();
        $incident->setStatus('open');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getStatus() === 'resolved';
            }));

        $result = $this->service->resolveIncident(1, 'Issue resolved');

        $this->assertInstanceOf(Incident::class, $result);
    }

    // ==================== CLOSE INCIDENT TESTS ====================

    public function testCloseIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->closeIncident(999);

        $this->assertNull($result);
    }

    public function testCloseIncidentSuccessfullyCloses(): void
    {
        $incident = new Incident();
        $incident->setStatus('resolved');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getStatus() === 'closed';
            }));

        $result = $this->service->closeIncident(1);

        $this->assertInstanceOf(Incident::class, $result);
    }

    // ==================== ESCALATE INCIDENT TESTS ====================

    public function testEscalateIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->escalateIncident(999);

        $this->assertNull($result);
    }

    public function testEscalateIncidentSuccessfullyEscalates(): void
    {
        $incident = new Incident();
        $incident->setSeverity('low');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getStatus() === 'escalated';
            }));

        $result = $this->service->escalateIncident(1);

        $this->assertInstanceOf(Incident::class, $result);
    }

    // ==================== GET INCIDENT TESTS ====================

    public function testGetIncidentReturnsNullWhenNotFound(): void
    {
        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getIncident(999);

        $this->assertNull($result);
    }

    public function testGetIncidentReturnsIncidentWhenFound(): void
    {
        $incident = new Incident();
        $incident->setTitle('Test Incident');

        $this->incidentRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($incident);

        $result = $this->service->getIncident(1);

        $this->assertNotNull($result);
        $this->assertSame($incident, $result);
    }
}
