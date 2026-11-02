<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Entity\AdministrativeProcedure;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureTest extends TestCase
{
    private function createProc(): AdministrativeProcedure
    {
        return new AdministrativeProcedure();
    }

    public function testConstructorInitializesTimestampsAndStatus(): void
    {
        $proc = $this->createProc();
        $this->assertNull($proc->getId());
        $this->assertNull($proc->getProcedureCode());
        $this->assertNull($proc->getName());
        $this->assertNull($proc->getDescription());
        $this->assertNull($proc->getDepartment());
        $this->assertNull($proc->getProcessingDays());
        $this->assertNull($proc->getRequiredDocuments());
        $this->assertNull($proc->getFee());
        $this->assertSame(AdministrativeProcedure::STATUS_ACTIVE, $proc->getStatus());
        $this->assertInstanceOf(\DateTime::class, $proc->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $proc->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $proc = $this->createProc();

        $proc->setProcedureCode('PROC-001');
        $this->assertSame('PROC-001', $proc->getProcedureCode());

        $proc->setName('Business License');
        $this->assertSame('Business License', $proc->getName());

        $proc->setDescription('Apply for a business operating license');
        $this->assertSame('Apply for a business operating license', $proc->getDescription());

        $proc->setDepartment('Commerce');
        $this->assertSame('Commerce', $proc->getDepartment());

        $proc->setProcessingDays(15);
        $this->assertSame(15, $proc->getProcessingDays());

        $docs = ['ID card', 'Proof of address'];
        $proc->setRequiredDocuments($docs);
        $this->assertSame($docs, $proc->getRequiredDocuments());

        $proc->setFee('50.00');
        $this->assertSame('50.00', $proc->getFee());

        $proc->setStatus(AdministrativeProcedure::STATUS_INACTIVE);
        $this->assertSame(AdministrativeProcedure::STATUS_INACTIVE, $proc->getStatus());
    }
}
