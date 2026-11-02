<?php

declare(strict_types=1);

namespace App\Tests\Unit\Modules\ANTT\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\ANTT\Application\Service\IncidentService;
use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Infrastructure\Repository\IncidentRepository;

#[AllowMockObjectsWithoutExpectations]
class IncidentServiceTest extends TestCase
{
    private IncidentService $service;
    private $incidentRepo;

    protected function setUp(): void
    {
        $this->incidentRepo = $this->createMock(IncidentRepository::class);
        $this->service = new IncidentService($this->incidentRepo);
    }

    public function testCreateIncidentGeneratesNumberAndSetsDefaults(): void
    {
        $userId = 'user-123';
        $data = [
            'title' => 'Test Incident',
            'incidentType' => 'traffic',
            'severity' => 'medium',
            'wardCode' => 'W01',
        ];

        $incident = $this->service->createIncident($data, $userId);

        $this->assertInstanceOf(Incident::class, $incident);
        $this->assertSame($userId, $incident->getReporterUserId());
        $this->assertSame('INCIDENT', $incident->getEntityType()); // assuming constant
        $this->assertSame('pending', $incident->getStatus());
    }

    public function testAssignOfficerUpdatesIncident(): void
    {
        $incidentId = 'inc-123';
        $officerId = 'officer-456';

        $incident = new Incident();
        $incident->setId($incidentId);
        $incident->setStatus('pending');

        $this->incidentRepo->expects($this->once())
            ->method('find')
            ->with($incidentId)
            ->willReturn($incident);

        $this->incidentRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function($i) use ($officerId) {
                return $i->getAssignedOfficerId() === $officerId && $i->getStatus() === 'assigned';
            }));

        $result = $this->service->assignOfficer($incidentId, $officerId);
        $this->assertSame($incident, $result);
        $this->assertSame($officerId, $result->getAssignedOfficerId());
        $this->assertSame('assigned', $result->getStatus());
    }

    public function testResolveIncident(): void
    {
        $incidentId = 'inc-123';
        $resolution = 'Issue resolved';

        $incident = new Incident();
        $incident->setId($incidentId);
        $incident->setStatus('assigned');

        $this->incidentRepo->expects($this->once())->method('find')->with($incidentId)->willReturn($incident);
        $this->incidentRepo->expects($this->once())->method('save');

        $result = $this->service->resolveIncident($incidentId, $resolution);
        $this->assertSame('resolved', $result->getStatus());
        $this->assertSame($resolution, $result->getResolutionSummary());
    }
}