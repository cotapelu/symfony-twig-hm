<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\AdministrativeProcedure;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureTest extends TestCase
{
    private function createProc(
        string $id = 'proc-id',
        string $code = 'ADM-001',
        string $name = 'Building Permit'
    ): AdministrativeProcedure {
        return new AdministrativeProcedure($id, $code, $name);
    }

    public function testConstructor(): void
    {
        $p = $this->createProc();
        $this->assertSame('proc-id', $p->getId());
        $this->assertSame('ADM-001', $p->getCode());
        $this->assertSame('Building Permit', $p->getName());
        $this->assertNull($p->getDescription());
        $this->assertNull($p->getRequirements());
        $this->assertSame('ACTIVE', $p->getStatus());
        $this->assertInstanceOf(\DateTime::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $p->getUpdatedAt());
        $this->assertTrue($p->isActive());
        $this->assertFalse($p->isInactive());
        $this->assertFalse($p->isArchived());
    }

    public function testSetCode(): void
    {
        $p = $this->createProc();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setCode('ADM-002');
        $this->assertSame('ADM-002', $p->getCode());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $p = $this->createProc();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setName('Marriage License');
        $this->assertSame('Marriage License', $p->getName());
        $this->assertNotEquals($original, $p->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $p = $this->createProc();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setDescription('Required for all buildings over 3 stories');
        $this->assertSame('Required for all buildings over 3 stories', $p->getDescription());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setDescription(null);
        $this->assertNull($p->getDescription());
    }

    public function testSetRequirements(): void
    {
        $p = $this->createProc();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $reqs = ['id_card', 'property_deed'];
        $p->setRequirements($reqs);
        $this->assertSame($reqs, $p->getRequirements());
        $this->assertNotEquals($original, $p->getUpdatedAt());
        $p->setRequirements(null);
        $this->assertNull($p->getRequirements());
    }

    public function testSetStatus(): void
    {
        $p = $this->createProc();
        $original = $p->getUpdatedAt();
        usleep(1000);
        $p->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $p->getStatus());
        $this->assertTrue($p->isInactive());
        $this->assertFalse($p->isActive());
        $this->assertNotEquals($original, $p->getUpdatedAt());

        $p->setStatus('ARCHIVED');
        $this->assertTrue($p->isArchived());
        $this->assertFalse($p->isActive());
    }
}
