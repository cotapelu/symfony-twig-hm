<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ProcedureApproval;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApprovalTest extends TestCase
{
    private function createApproval(
        string $id = 'approval-id',
        string $applicationId = 'app-1',
        string $decision = 'APPROVED'
    ): ProcedureApproval {
        return new ProcedureApproval($id, $applicationId, $decision);
    }

    public function testConstructor(): void
    {
        $a = $this->createApproval();
        $this->assertSame('approval-id', $a->getId());
        $this->assertSame('app-1', $a->getApplicationId());
        $this->assertNull($a->getOfficerId());
        $this->assertSame('APPROVED', $a->getDecision());
        $this->assertNull($a->getNotes());
        $this->assertInstanceOf(\DateTime::class, $a->getCreatedAt());
        $this->assertTrue($a->isApproved());
        $this->assertFalse($a->isRejected());
        $this->assertFalse($a->isPending());
        $this->assertFalse($a->isCancelled());
    }

    public function testSetApplicationId(): void
    {
        $a = $this->createApproval();
        $a->setApplicationId('app-2');
        $this->assertSame('app-2', $a->getApplicationId());
    }

    public function testSetOfficerId(): void
    {
        $a = $this->createApproval();
        $a->setOfficerId('officer-1');
        $this->assertSame('officer-1', $a->getOfficerId());
        $a->setOfficerId(null);
        $this->assertNull($a->getOfficerId());
    }

    public function testSetDecision(): void
    {
        $a = $this->createApproval();
        $a->setDecision('REJECTED');
        $this->assertSame('REJECTED', $a->getDecision());
        $this->assertTrue($a->isRejected());
        $this->assertFalse($a->isApproved());

        $a->setDecision('PENDING');
        $this->assertTrue($a->isPending());
        $a->setDecision('CANCELLED');
        $this->assertTrue($a->isCancelled());
    }

    public function testSetNotes(): void
    {
        $a = $this->createApproval();
        $a->setNotes('Needs more info');
        $this->assertSame('Needs more info', $a->getNotes());
        $a->setNotes(null);
        $this->assertNull($a->getNotes());
    }

    public function testDecisionHelpers(): void
    {
        $a = $this->createApproval();
        $decisions = ['APPROVED', 'REJECTED', 'PENDING', 'CANCELLED'];
        foreach ($decisions as $d) {
            $a->setDecision($d);
            $method = 'is' . ucfirst(strtolower($d));
            $this->assertTrue($a->$method(), "Failed for decision $d");
        }
    }
}
