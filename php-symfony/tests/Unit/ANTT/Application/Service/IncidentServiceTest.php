<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use App\ANTT\Application\DTO\CreateIncidentRequest;
use App\ANTT\Application\DTO\UpdateIncidentRequest;
use App\ANTT\Application\DTO\IncidentStatistics;
use App\ANTT\Application\Service\IncidentService;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use TypeError;

#[AllowMockObjectsWithoutExpectations]
class IncidentServiceTest extends TestCase
{
    private IncidentRepositoryInterface $incidentRepoMock;
    private ?LoggerInterface $loggerMock;
    private IncidentService $service;

    protected function setUp(): void
    {
        $this->incidentRepoMock = $this->createMock(IncidentRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new IncidentService($this->incidentRepoMock, $this->loggerMock);
    }

    private function createIncident(array $data = []): Incident
    {
        $incident = new Incident();
        if (isset($data['id'])) {
            $ref = new \ReflectionClass($incident);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($incident, $data['id']);
        }
        if (isset($data['incidentNumber'])) {
            // set via reflection because it's generated in constructor
            $ref = new \ReflectionClass($incident);
            $prop = $ref->getProperty('incidentNumber');
            $prop->setAccessible(true);
            $prop->setValue($incident, $data['incidentNumber']);
        }
        if (isset($data['title'])) {
            $incident->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $incident->setDescription($data['description']);
        }
        if (isset($data['incidentType'])) {
            $incident->setIncidentType($data['incidentType']);
        }
        if (isset($data['severity'])) {
            $incident->setSeverity($data['severity']);
        }
        if (isset($data['status'])) {
            $incident->setStatus($data['status']);
        }
        if (isset($data['wardCode'])) {
            $incident->setWardCode($data['wardCode']);
        }
        if (isset($data['reporterId'])) {
            $incident->setReporterId($data['reporterId']);
        }
        return $incident;
    }

    // ==================== createIncident ====================

    public function testCreateIncidentSuccess(): void
    {
        $this->incidentRepoMock->method('save')->willReturnArgument(0);

        $request = new CreateIncidentRequest();
        $request->title = 'Test incident';
        $request->incidentType = 'traffic';
        $request->wardCode = 'W001';
        $request->locationAddress = '123 Street';
        $request->latitude = 10.0;
        $request->longitude = 106.0;
        $request->reporterName = 'John';
        $request->reporterPhone = '+84912345678';
        $request->reporterAnonymous = false;

        $result = $this->service->createIncident($request, 'user-123');

        $this->assertInstanceOf(Incident::class, $result);
        $this->assertSame('Test incident', $result->getTitle());
        $this->assertSame('traffic', $result->getIncidentType());
        $this->assertSame('W001', $result->getWardCode());
        $this->assertSame('user-123', $result->getReporterId());
    }

    public function testCreateIncidentThrowsIfTitleEmpty(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = '   ';
        $request->incidentType = 'traffic';
        $this->expectException(InvalidArgumentException::class);
        $this->service->createIncident($request, 'u');
    }

    public function testCreateIncidentThrowsIfTypeEmpty(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'Title';
        $request->incidentType = '   ';
        $this->expectException(InvalidArgumentException::class);
        $this->service->createIncident($request, 'u');
    }

    public function testCreateIncidentThrowsIfInvalidLatitude(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'T';
        $request->incidentType = 'traffic';
        $request->latitude = 100; // out of range
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90 degrees');
        $this->service->createIncident($request, 'u');
    }

    public function testCreateIncidentThrowsIfInvalidLongitude(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'T';
        $request->incidentType = 'traffic';
        $request->longitude = 200; // out of range
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Longitude must be between -180 and 180 degrees');
        $this->service->createIncident($request, 'u');
    }

    public function testCreateIncidentThrowsIfPhoneInvalid(): void
    {
        $request = new CreateIncidentRequest();
        $request->title = 'T';
        $request->incidentType = 'traffic';
        $request->reporterAnonymous = false;
        $request->reporterPhone = 'invalid-phone';
        $this->expectException(InvalidArgumentException::class);
        $this->service->createIncident($request, 'u');
    }

    // ==================== updateIncident ====================

    public function testUpdateIncidentSuccess(): void
    {
        $incident = $this->createIncident(['id' => 1, 'title' => 'Old', 'description' => 'Old desc', 'incidentType' => 'traffic', 'severity' => 'low']);
        $this->incidentRepoMock->method('find')->with(1)->willReturn($incident);
        $this->incidentRepoMock->expects($this->once())->method('save')->with($incident);

        $request = new UpdateIncidentRequest();
        $request->title = 'New title';
        $request->severity = 'high';
        $result = $this->service->updateIncident(1, $request);

        $this->assertSame($incident, $result);
        $this->assertSame('New title', $result->getTitle());
        $this->assertSame('high', $result->getSeverity());
        $this->assertSame('traffic', $result->getIncidentType()); // unchanged
    }

    public function testUpdateIncidentReturnsNullIfNotFound(): void
    {
        $this->incidentRepoMock->method('find')->with(2)->willReturn(null);
        $request = new UpdateIncidentRequest();
        $result = $this->service->updateIncident(2, $request);
        $this->assertNull($result);
    }

    public function testUpdateIncidentThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->updateIncident(0, new UpdateIncidentRequest());
    }

    // ==================== assignIncident ====================

    public function testAssignIncidentSuccess(): void
    {
        $incident = $this->createIncident(['id' => 1]);
        $this->incidentRepoMock->method('find')->with(1)->willReturn($incident);
        $this->incidentRepoMock->expects($this->once())->method('save')->with($incident);

        $result = $this->service->assignIncident(1, 'officer-99', 'team-alpha');

        $this->assertSame($incident, $result);
        $this->assertSame('officer-99', $result->getAssignedOfficerId());
        $this->assertSame('team-alpha', $result->getAssignedTeam());
    }

    public function testAssignIncidentReturnsNullIfNotFound(): void
    {
        $this->incidentRepoMock->method('find')->with(1)->willReturn(null);
        $result = $this->service->assignIncident(1, 'off', 'team');
        $this->assertNull($result);
    }

    public function testAssignIncidentThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->assignIncident(-1, 'off', 'team');
    }

    public function testAssignIncidentThrowsIfOfficerIdEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->assignIncident(1, '', 'team');
    }

    // ==================== resolveIncident ====================

    public function testResolveIncidentSuccess(): void
    {
        $incident = $this->createIncident(['id' => 1, 'status' => 'in_progress']);
        $this->incidentRepoMock->method('find')->with(1)->willReturn($incident);
        $this->incidentRepoMock->expects($this->once())->method('save')->with($incident);

        $result = $this->service->resolveIncident(1, 'Fixed');

        $this->assertSame($incident, $result);
        $this->assertSame('resolved', $result->getStatus());
        $this->assertSame('Fixed', $result->getResolutionSummary());
    }

    public function testResolveIncidentReturnsNullIfNotFound(): void
    {
        $this->incidentRepoMock->method('find')->with(1)->willReturn(null);
        $result = $this->service->resolveIncident(1, 'summary');
        $this->assertNull($result);
    }

    public function testResolveIncidentThrowsIfInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->resolveIncident(0, 'summary');
    }

    public function testResolveIncidentThrowsIfSummaryEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->resolveIncident(1, '   ');
    }

    // ==================== getOpenIncidents ====================

    public function testGetOpenIncidents(): void
    {
        $incidents = [$this->createIncident(), $this->createIncident()];
        $this->incidentRepoMock->method('findOpenIncidents')->with(50, 0)->willReturn($incidents);
        $result = $this->service->getOpenIncidents();
        $this->assertSame($incidents, $result);
    }

    public function testGetOpenIncidentsWithLimitOffset(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepoMock->method('findOpenIncidents')->with(10, 5)->willReturn($incidents);
        $result = $this->service->getOpenIncidents(10, 5);
        $this->assertSame($incidents, $result);
    }

    // ==================== getRecentIncidents ====================

    public function testGetRecentIncidents(): void
    {
        $incidents = [$this->createIncident(), $this->createIncident()];
        $this->incidentRepoMock->method('findRecent')->with(10)->willReturn($incidents);
        $result = $this->service->getRecentIncidents(10);
        $this->assertSame($incidents, $result);
    }

    public function testGetRecentIncidentsThrowsIfInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getRecentIncidents(0);
    }

    // ==================== getIncidentsByWard ====================

    public function testGetIncidentsByWard(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepoMock->method('findByWardCode')->with('W01')->willReturn($incidents);
        $result = $this->service->getIncidentsByWard('W01');
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByWardThrowsIfEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getIncidentsByWard('');
    }

    // ==================== getIncidentsByOfficer ====================

    public function testGetIncidentsByOfficer(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepoMock->method('findByAssignedOfficer')->with('OFF123')->willReturn($incidents);
        $result = $this->service->getIncidentsByOfficer('OFF123');
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByOfficerThrowsIfEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getIncidentsByOfficer('');
    }

    // ==================== getIncidentsByReporter ====================

    public function testGetIncidentsByReporter(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepoMock->method('findByReporterId')->with('REP123')->willReturn($incidents);
        $result = $this->service->getIncidentsByReporter('REP123');
        $this->assertSame($incidents, $result);
    }

    public function testGetIncidentsByReporterThrowsIfEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getIncidentsByReporter('');
    }

    // ==================== searchIncidents ====================

    public function testSearchIncidents(): void
    {
        $incidents = [$this->createIncident()];
        $this->incidentRepoMock->method('search')->with(['status' => 'open'])->willReturn($incidents);
        $result = $this->service->searchIncidents(['status' => 'open']);
        $this->assertSame($incidents, $result);
    }

    public function testSearchIncidentsThrowsIfCriteriaNotArray(): void
    {
        $this->expectException(TypeError::class);
        $this->service->searchIncidents('not array');
    }

    // ==================== getStatistics ====================

    public function testGetStatisticsOverall(): void
    {
        $this->incidentRepoMock->method('countByStatus')->willReturn(['reported' => 5, 'assigned' => 2, 'in_progress' => 3, 'resolved' => 10, 'closed' => 8]);
        $this->incidentRepoMock->method('countByType')->willReturn(['traffic' => 8, 'crime' => 12]);

        $stats = $this->service->getStatistics();

        $this->assertInstanceOf(IncidentStatistics::class, $stats);
        $this->assertSame(28, $stats->total); // sum of all statuses
        $this->assertSame(10, $stats->open); // reported+assigned+in_progress
        $this->assertSame(18, $stats->resolved);
        $this->assertSame(['traffic' => 8, 'crime' => 12], $stats->byType);
    }

    public function testGetStatisticsByWard(): void
    {
        $this->incidentRepoMock->method('countByWardCode')->with('W01')->willReturn(['reported' => 1, 'resolved' => 2]);
        $this->incidentRepoMock->method('countByTypeWithWard')->with('W01')->willReturn(['traffic' => 2]);

        $stats = $this->service->getStatistics('W01');

        $this->assertSame(3, $stats->total);
        $this->assertSame(1, $stats->open);
        $this->assertSame(2, $stats->resolved);
        $this->assertSame(['traffic' => 2], $stats->byType);
    }
}
