<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Domain\Entity\Incident;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IncidentTest extends TestCase
{
    private function createIncident(): Incident
    {
        return new Incident();
    }

    public function testConstructorInitializesDefaults(): void
    {
        $incident = $this->createIncident();
        $this->assertNull($incident->getId());
        $this->assertNotEmpty($incident->getIncidentNumber());
        $this->assertStringStartsWith('BAO', $incident->getIncidentNumber());
        $this->assertSame('reported', $incident->getStatus());
        $this->assertSame('medium', $incident->getSeverity());
        $this->assertInstanceOf(\DateTimeImmutable::class, $incident->getReportedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $incident->getCreatedAt());
    }

    public function testSetTitle(): void
    {
        $incident = $this->createIncident();
        $incident->setTitle('Test Title');
        $this->assertSame('Test Title', $incident->getTitle());
    }

    public function testSetDescription(): void
    {
        $incident = $this->createIncident();
        $incident->setDescription('Description');
        $this->assertSame('Description', $incident->getDescription());
        $incident->setDescription(null);
        $this->assertNull($incident->getDescription());
    }

    public function testSetIncidentType(): void
    {
        $incident = $this->createIncident();
        $incident->setIncidentType('fraud');
        $this->assertSame('fraud', $incident->getIncidentType());
    }

    public function testSetSeverity(): void
    {
        $incident = $this->createIncident();
        $incident->setSeverity('high');
        $this->assertSame('high', $incident->getSeverity());
    }

    public function testSetStatus(): void
    {
        $incident = $this->createIncident();
        $incident->setStatus('assigned');
        $this->assertSame('assigned', $incident->getStatus());
    }

    public function testSetReporterId(): void
    {
        $incident = $this->createIncident();
        $incident->setReporterId('rep-123');
        $this->assertSame('rep-123', $incident->getReporterId());
        $incident->setReporterId(null);
        $this->assertNull($incident->getReporterId());
    }

    public function testSetReporterName(): void
    {
        $incident = $this->createIncident();
        $incident->setReporterName('Alice');
        $this->assertSame('Alice', $incident->getReporterName());
        $incident->setReporterName(null);
        $this->assertNull($incident->getReporterName());
    }

    public function testSetReporterPhone(): void
    {
        $incident = $this->createIncident();
        $incident->setReporterPhone('1234567890');
        $this->assertSame('1234567890', $incident->getReporterPhone());
        $incident->setReporterPhone(null);
        $this->assertNull($incident->getReporterPhone());
    }

    public function testSetReporterAnonymous(): void
    {
        $incident = $this->createIncident();
        $incident->setReporterAnonymous(true);
        $this->assertTrue($incident->isReporterAnonymous());
        $incident->setReporterAnonymous(false);
        $this->assertFalse($incident->isReporterAnonymous());
    }

    public function testSetLocation(): void
    {
        $incident = $this->createIncident();
        $incident->setLocation('123 Main', 10.0, 20.0);
        $this->assertSame('123 Main', $incident->getLocationAddress());
        $this->assertSame(10.0, $incident->getLatitude());
        $this->assertSame(20.0, $incident->getLongitude());
    }

    public function testSetWardCode(): void
    {
        $incident = $this->createIncident();
        $incident->setWardCode('W001');
        $this->assertSame('W001', $incident->getWardCode());
        $incident->setWardCode(null);
        $this->assertNull($incident->getWardCode());
    }

    public function testSetAssignedOfficerId(): void
    {
        $incident = $this->createIncident();
        $incident->setAssignedOfficerId('off-123');
        $this->assertSame('off-123', $incident->getAssignedOfficerId());
        $incident->setAssignedOfficerId(null);
        $this->assertNull($incident->getAssignedOfficerId());
    }

    public function testSetAssignedTeam(): void
    {
        $incident = $this->createIncident();
        $incident->setAssignedTeam('Team A');
        $this->assertSame('Team A', $incident->getAssignedTeam());
        $incident->setAssignedTeam(null);
        $this->assertNull($incident->getAssignedTeam());
    }

    public function testAssignTo(): void
    {
        $incident = $this->createIncident();
        $incident->assignTo('off-123', 'Team X');
        $this->assertSame('off-123', $incident->getAssignedOfficerId());
        $this->assertSame('Team X', $incident->getAssignedTeam());
        $this->assertSame('assigned', $incident->getStatus());
    }

    public function testEscalate(): void
    {
        $incident = $this->createIncident();
        $incident->escalate();
        $this->assertSame('escalated', $incident->getStatus());
    }

    public function testResolve(): void
    {
        $incident = $this->createIncident();
        $incident->resolve('Resolved with action');
        $this->assertSame('resolved', $incident->getStatus());
        $this->assertSame('Resolved with action', $incident->getResolutionSummary());
        $this->assertNotNull($incident->getResolvedAt());
    }

    public function testClose(): void
    {
        $incident = $this->createIncident();
        $incident->close();
        $this->assertSame('closed', $incident->getStatus());
        $this->assertNotNull($incident->getClosedAt());
    }

    public function testIsOpen(): void
    {
        $incident = $this->createIncident();
        $this->assertTrue($incident->isOpen());
        $incident->setStatus('assigned');
        $this->assertTrue($incident->isOpen());
        $incident->setStatus('in_progress');
        $this->assertTrue($incident->isOpen());
        $incident->setStatus('resolved');
        $this->assertFalse($incident->isOpen());
        $incident->setStatus('closed');
        $this->assertFalse($incident->isOpen());
    }

    public function testIsResolved(): void
    {
        $incident = $this->createIncident();
        $incident->setStatus('resolved');
        $this->assertTrue($incident->isResolved());
        $incident->setStatus('closed');
        $this->assertTrue($incident->isResolved());
        $incident->setStatus('assigned');
        $this->assertFalse($incident->isResolved());
    }

    public function testToArrayStructure(): void
    {
        $incident = $this->createIncident();
        $incident->setTitle('Incident Title')
            ->setDescription('Desc')
            ->setIncidentType('theft')
            ->setSeverity('high')
            ->setReporterId('rep-123')
            ->setReporterName('Reporter')
            ->setReporterPhone('123456')
            ->setReporterAnonymous(false)
            ->setLocation('Address', 10.0, 20.0)
            ->setWardCode('W002')
            ->assignTo('off-1', 'Team1')
            ->resolve('All good');

        $arr = $incident->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('incident_number', $arr);
        $this->assertSame('Incident Title', $arr['title']);
        $this->assertSame('Desc', $arr['description']);
        $this->assertSame('theft', $arr['incident_type']);
        $this->assertSame('high', $arr['severity']);
        $this->assertArrayHasKey('location', $arr);
        $this->assertSame('Address', $arr['location']['address']);
        $this->assertSame(10.0, $arr['location']['latitude']);
        $this->assertSame(20.0, $arr['location']['longitude']);
        $this->assertSame('off-1', $arr['assigned_officer_id']);
        $this->assertSame('Team1', $arr['assigned_team']);
    }
}
