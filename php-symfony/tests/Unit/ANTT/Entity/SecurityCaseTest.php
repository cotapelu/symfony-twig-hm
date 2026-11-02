<?php declare(strict_types=1);

namespace App\Tests\Unit\ANTT\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\ANTT\Domain\Entity\SecurityCase;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SecurityCaseTest extends TestCase
{
    private function createCase(): SecurityCase
    {
        return new SecurityCase();
    }

    public function testConstructorInitializesDefaults(): void
    {
        $case = $this->createCase();
        $this->assertNull($case->getId());
        $this->assertNotEmpty($case->getCaseNumber());
        $this->assertStringStartsWith('HS', $case->getCaseNumber());
        $this->assertSame('open', $case->getStatus());
        $this->assertSame('medium', $case->getPriority());
        $this->assertInstanceOf(\DateTimeImmutable::class, $case->getOpenedAt());
        // createdAt and openedAt are set to same time in constructor
        $this->assertSame($case->getCreatedAt()->format('Y-m-d H:i:s'), $case->getOpenedAt()->format('Y-m-d H:i:s'));
    }

    public function testSetTitle(): void
    {
        $case = $this->createCase();
        $case->setTitle('Test Title');
        $this->assertSame('Test Title', $case->getTitle());
    }

    public function testSetDescription(): void
    {
        $case = $this->createCase();
        $case->setDescription('Description');
        $this->assertSame('Description', $case->getDescription());
        $case->setDescription(null);
        $this->assertNull($case->getDescription());
    }

    public function testSetCaseType(): void
    {
        $case = $this->createCase();
        $case->setCaseType('fraud');
        $this->assertSame('fraud', $case->getCaseType());
    }

    public function testSetPriority(): void
    {
        $case = $this->createCase();
        $case->setPriority('high');
        $this->assertSame('high', $case->getPriority());
    }

    public function testSetStatusUpdatesTimestamp(): void
    {
        $case = $this->createCase();
        $this->assertNull($case->getUpdatedAt());

        $case->setStatus('under_investigation');
        $this->assertSame('under_investigation', $case->getStatus());
        $this->assertNotNull($case->getUpdatedAt());
    }

    public function testAssignTo(): void
    {
        $case = $this->createCase();
        $case->assignTo('off-123', 'John Doe', 'Team Alpha');
        $this->assertSame('off-123', $case->getInvestigationOfficerId());
        $this->assertSame('John Doe', $case->getInvestigationOfficerName());
        $this->assertSame('Team Alpha', $case->getAssignedTeam());
        $this->assertSame('under_investigation', $case->getStatus());
    }

    public function testLinkIncident(): void
    {
        $case = $this->createCase();
        $case->linkIncident(456);
        $this->assertSame(456, $case->getIncidentId());
    }

    public function testAddSuspect(): void
    {
        $case = $this->createCase();
        $suspect = ['name' => 'Suspect A', 'age' => 30];
        $case->addSuspect($suspect);
        $suspects = $case->getSuspects();
        $this->assertCount(1, $suspects);
        $this->assertSame($suspect, $suspects[0]);
    }

    public function testAddVictim(): void
    {
        $case = $this->createCase();
        $victim = ['name' => 'Victim B'];
        $case->addVictim($victim);
        $this->assertCount(1, $case->getVictims());
        $this->assertSame($victim, $case->getVictims()[0]);
    }

    public function testAddEvidence(): void
    {
        $case = $this->createCase();
        $evidence = ['type' => 'weapon', 'desc' => 'Knife'];
        $case->addEvidence($evidence);
        $this->assertCount(1, $case->getEvidence());
        $this->assertSame($evidence, $case->getEvidence()[0]);
    }

    public function testSetLocation(): void
    {
        $case = $this->createCase();
        $case->setLocation('123 Main St', 'W001');
        $this->assertSame('123 Main St', $case->getLocationAddress());
        $this->assertSame('W001', $case->getWardCode());
    }

    public function testResolve(): void
    {
        $case = $this->createCase();
        $case->resolve('Guilty', 'imprisonment');
        $this->assertSame('resolved', $case->getStatus());
        $this->assertSame('Guilty', $case->getResolution());
        $this->assertSame('imprisonment', $case->getLegalAction());
        $this->assertNotNull($case->getClosedAt());
    }

    public function testClose(): void
    {
        $case = $this->createCase();
        $case->close();
        $this->assertSame('closed', $case->getStatus());
        $this->assertNotNull($case->getClosedAt());
    }

    public function testEscalate(): void
    {
        $case = $this->createCase();
        $case->escalate();
        $this->assertSame('escalated', $case->getStatus());
        $this->assertSame('high', $case->getPriority());
    }

    public function testIsOpen(): void
    {
        $case = $this->createCase();
        $this->assertTrue($case->isOpen());

        $case->close();
        $this->assertFalse($case->isOpen());

        $case->setStatus('open');
        $this->assertTrue($case->isOpen());

        $case->setStatus('under_investigation');
        $this->assertTrue($case->isOpen());

        $case->setStatus('escalated');
        $this->assertTrue($case->isOpen());

        $case->setStatus('resolved');
        $this->assertFalse($case->isOpen());

        $case->setStatus('closed');
        $this->assertFalse($case->isOpen());
    }

    public function testToArrayStructure(): void
    {
        $case = $this->createCase();
        $case->setTitle('Case Title')
            ->setDescription('Desc')
            ->setCaseType('fraud')
            ->setPriority('high')
            ->setStatus('under_investigation')
            ->assignTo('off-123', 'Alice', 'TeamX')
            ->linkIncident(789)
            ->addSuspect(['name' => 'Bob'])
            ->addVictim(['name' => 'Charlie'])
            ->addEvidence(['type' => 'doc'])
            ->setLocation('Address', 'W002')
            ->resolve('Resolved', 'prosecuted');

        $arr = $case->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('case_number', $arr);
        $this->assertSame('Case Title', $arr['title']);
        $this->assertSame('Desc', $arr['description']);
        $this->assertSame('fraud', $arr['case_type']);
        $this->assertSame('high', $arr['priority']);
        $this->assertSame('resolved', $arr['status']);
        $this->assertSame(['id' => 'off-123', 'name' => 'Alice'], $arr['investigation_officer']);
        $this->assertSame('TeamX', $arr['assigned_team']);
        $this->assertSame(789, $arr['incident_id']);
        $this->assertSame(1, $arr['suspects_count']);
        $this->assertSame(1, $arr['victims_count']);
        $this->assertSame(1, $arr['evidence_count']);
        $this->assertSame('Address', $arr['location']);
        $this->assertSame('W002', $arr['ward_code']);
        $this->assertSame('Resolved', $arr['resolution']);
        $this->assertSame('prosecuted', $arr['legal_action']);
    }
}
