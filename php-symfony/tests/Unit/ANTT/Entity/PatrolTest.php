<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Domain\Entity\Patrol;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PatrolTest extends TestCase
{
    private function createPatrol(): Patrol
    {
        return new Patrol();
    }

    public function testConstructorInitializesDefaults(): void
    {
        $patrol = $this->createPatrol();
        $this->assertNull($patrol->getId()); // new entity id is null until persisted
        $this->assertNotEmpty($patrol->getPatrolNumber());
        $this->assertSame('scheduled', $patrol->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $patrol->getPatrolDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $patrol->getStartTime());
    }

    public function testSetTeamId(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setTeamId('team-123');
        $this->assertSame('team-123', $patrol->getTeamId());
    }

    public function testSetTeamName(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setTeamName('Security Team A');
        $this->assertSame('Security Team A', $patrol->getTeamName());
    }

    public function testSetTeamLeaderId(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setTeamLeaderId('officer-123');
        $this->assertSame('officer-123', $patrol->getTeamLeaderId());
        $patrol->setTeamLeaderId(null);
        $this->assertNull($patrol->getTeamLeaderId());
    }

    public function testSetTeamLeaderName(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setTeamLeaderName('John Doe');
        $this->assertSame('John Doe', $patrol->getTeamLeaderName());
        $patrol->setTeamLeaderName(null);
        $this->assertNull($patrol->getTeamLeaderName());
    }

    public function testSetPatrolDate(): void
    {
        $patrol = $this->createPatrol();
        $date = new \DateTimeImmutable('2024-06-01 08:00:00');
        $patrol->setPatrolDate($date);
        $this->assertSame($date, $patrol->getPatrolDate());
    }

    public function testSetStartTime(): void
    {
        $patrol = $this->createPatrol();
        $time = new \DateTimeImmutable('2024-06-01 09:00:00');
        $patrol->setStartTime($time);
        $this->assertSame($time, $patrol->getStartTime());
    }

    public function testSetEndTime(): void
    {
        $patrol = $this->createPatrol();
        $end = new \DateTimeImmutable('2024-06-01 17:00:00');
        $patrol->setEndTime($end);
        $this->assertSame($end, $patrol->getEndTime());
        $patrol->setEndTime(null);
        $this->assertNull($patrol->getEndTime());
    }

    public function testSetPatrolType(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setPatrolType('foot');
        $this->assertSame('foot', $patrol->getPatrolType());
    }

    public function testSetPatrolRoute(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setPatrolRoute('Route A -> B -> C');
        $this->assertSame('Route A -> B -> C', $patrol->getPatrolRoute());
        $patrol->setPatrolRoute(null);
        $this->assertNull($patrol->getPatrolRoute());
    }

    public function testSetAreaCovered(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setAreaCovered('Ward 1, District 2');
        $this->assertSame('Ward 1, District 2', $patrol->getAreaCovered());
        $patrol->setAreaCovered(null);
        $this->assertNull($patrol->getAreaCovered());
    }

    public function testSetCheckpoints(): void
    {
        $patrol = $this->createPatrol();
        $checkpoints = ['A', 'B', 'C'];
        $patrol->setCheckpoints($checkpoints);
        $this->assertSame($checkpoints, $patrol->getCheckpoints());
        $patrol->setCheckpoints(null);
        $this->assertNull($patrol->getCheckpoints());
    }

    public function testSetStatus(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setStatus('in_progress');
        $this->assertSame('in_progress', $patrol->getStatus());
    }

    public function testSetTotalMembers(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setTotalMembers(5);
        $this->assertSame(5, $patrol->getTotalMembers());
    }

    public function testSetFindingsSummary(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setFindingsSummary('No incidents found.');
        $this->assertSame('No incidents found.', $patrol->getFindingsSummary());
        $patrol->setFindingsSummary(null);
        $this->assertNull($patrol->getFindingsSummary());
    }

    public function testSetIncidentsReported(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setIncidentsReported(2);
        $this->assertSame(2, $patrol->getIncidentsReported());
    }

    public function testSetViolationsRecorded(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setViolationsRecorded(3);
        $this->assertSame(3, $patrol->getViolationsRecorded());
    }

    public function testSetNotes(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setNotes('Routine patrol.');
        $this->assertSame('Routine patrol.', $patrol->getNotes());
        $patrol->setNotes(null);
        $this->assertNull($patrol->getNotes());
    }

    public function testSetWardCode(): void
    {
        $patrol = $this->createPatrol();
        $patrol->setWardCode('W001');
        $this->assertSame('W001', $patrol->getWardCode());
        $patrol->setWardCode(null);
        $this->assertNull($patrol->getWardCode());
    }
}
