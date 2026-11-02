<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Entity\ProcedureApplication;
use App\Government\Entity\ProcedureApproval;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApprovalTest extends TestCase
{
    private function createApplication(): ProcedureApplication
    {
        $citizen = new \App\Citizen\Entity\Citizen(
            identityNumber: '111',
            fullName: 'Alice',
            dateOfBirth: new \DateTime(),
            gender: 'F'
        );
        $proc = new \App\Government\Entity\AdministrativeProcedure();
        $proc->setProcedureCode('P-001');
        $proc->setName('Test Procedure');
        return new ProcedureApplication($citizen, $proc);
    }

    private function setApplicationId(ProcedureApplication $app, int $id): void
    {
        $ref = new \ReflectionProperty($app, 'id');
        $ref->setAccessible(true);
        $ref->setValue($app, $id);
    }

    private function setApprovalId(ProcedureApproval $approval, int $id): void
    {
        $ref = new \ReflectionProperty($approval, 'id');
        $ref->setAccessible(true);
        $ref->setValue($approval, $id);
    }

    public function testConstructorInitializesProperties(): void
    {
        $app = $this->createApplication();
        $approval = new ProcedureApproval($app);

        $this->assertSame($app, $approval->getApplication());
        $this->assertSame(ProcedureApproval::DECISION_PENDING, $approval->getDecision());
        $this->assertTrue($approval->isPending());
        $this->assertFalse($approval->isApproved());
        $this->assertFalse($approval->isRejected());
        $this->assertNull($approval->getNotes());
        $this->assertInstanceOf(\DateTime::class, $approval->getApprovedAt());
        $this->assertNull($approval->getOfficer());
    }

    public function testGettersAndSetters(): void
    {
        $app = $this->createApplication();
        $approval = new ProcedureApproval($app);

        $approval->setDecision(ProcedureApproval::DECISION_APPROVED);
        $this->assertSame(ProcedureApproval::DECISION_APPROVED, $approval->getDecision());
        $this->assertTrue($approval->isApproved());
        $this->assertFalse($approval->isPending());

        $approval->setDecision(ProcedureApproval::DECISION_REJECTED);
        $this->assertTrue($approval->isRejected());

        $notes = 'All documents in order';
        $approval->setNotes($notes);
        $this->assertSame($notes, $approval->getNotes());

        $officer = new \App\Government\Entity\GovOfficer('Dept', 'Position');
        $approval->setOfficer($officer);
        $this->assertSame($officer, $approval->getOfficer());

        $approval->setOfficer(null);
        $this->assertNull($approval->getOfficer());
    }

    public function testToArray(): void
    {
        $app = $this->createApplication();
        $this->setApplicationId($app, 42);
        $approval = new ProcedureApproval($app);
        $this->setApprovalId($approval, 7);
        $approval->setDecision(ProcedureApproval::DECISION_APPROVED);
        $officer = new \App\Government\Entity\GovOfficer('Safety', 'Inspector');
        // Need to set officer id for toArray? Use reflection as well.
        $refId = new \ReflectionProperty($officer, 'id');
        $refId->setAccessible(true);
        $refId->setValue($officer, 'OFF-1');
        $approval->setOfficer($officer);

        $arr = $approval->toArray();
        $this->assertSame(7, $arr['id']);
        $this->assertSame(42, $arr['applicationId']);
        $this->assertSame('OFF-1', $arr['officerId']);
        $this->assertSame(ProcedureApproval::DECISION_APPROVED, $arr['decision']);
        $this->assertNull($arr['notes']); // not set
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $arr['approvedAt']);
    }

    public function testToArrayWithNullOfficer(): void
    {
        $app = $this->createApplication();
        $this->setApplicationId($app, 5);
        $approval = new ProcedureApproval($app);
        $this->setApprovalId($approval, 10);
        $approval->setDecision(ProcedureApproval::DECISION_REJECTED);
        $approval->setNotes('Insufficient documents');

        $arr = $approval->toArray();
        $this->assertSame(10, $arr['id']);
        $this->assertSame(5, $arr['applicationId']);
        $this->assertNull($arr['officerId']);
        $this->assertSame(ProcedureApproval::DECISION_REJECTED, $arr['decision']);
        $this->assertSame('Insufficient documents', $arr['notes']);
    }
}
