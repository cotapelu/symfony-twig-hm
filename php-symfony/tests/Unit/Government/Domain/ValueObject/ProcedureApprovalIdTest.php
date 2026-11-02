<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\ProcedureApprovalId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApprovalIdTest extends TestCase
{
    public function testConstructorGeneratesUuidIfNull(): void
    {
        $id = new ProcedureApprovalId();
        $this->assertNotNull($id->toString());
        $this->assertSame($id->toString(), (string)$id);
    }

    public function testConstructorAcceptsGivenId(): void
    {
        $id = new ProcedureApprovalId('pa-123');
        $this->assertSame('pa-123', $id->toString());
        $this->assertSame('pa-123', (string)$id);
    }

    public function testEquals(): void
    {
        $id1 = new ProcedureApprovalId('xyz');
        $id2 = new ProcedureApprovalId('xyz');
        $id3 = new ProcedureApprovalId('abc');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
