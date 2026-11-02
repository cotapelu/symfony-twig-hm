<?php declare(strict_types=1);

namespace App\Tests\Entity\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ProcedureApplication;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApplicationTest extends TestCase
{
    private function createApp(
        string $id = 'app-id',
        string $citizenId = 'citizen-1',
        string $procedureId = 'proc-1'
    ): ProcedureApplication {
        return new ProcedureApplication($id, $citizenId, $procedureId);
    }

    public function testConstructor(): void
    {
        $a = $this->createApp();
        $this->assertSame('app-id', $a->getId());
        $this->assertSame('citizen-1', $a->getCitizenId());
        $this->assertSame('proc-1', $a->getProcedureId());
        $this->assertSame('PENDING', $a->getStatus());
        $this->assertNull($a->getData());
        $this->assertInstanceOf(\DateTime::class, $a->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $a->getUpdatedAt());
        $this->assertTrue($a->isPending());
        $this->assertFalse($a->isApproved());
        $this->assertFalse($a->isRejected());
        $this->assertFalse($a->isProcessing());
        $this->assertFalse($a->isCompleted());
        $this->assertFalse($a->isCancelled());
    }

    public function testSetCitizenId(): void
    {
        $a = $this->createApp();
        $original = $a->getUpdatedAt();
        usleep(1000);
        $a->setCitizenId('citizen-2');
        $this->assertSame('citizen-2', $a->getCitizenId());
        $this->assertNotEquals($original, $a->getUpdatedAt());
    }

    public function testSetProcedureId(): void
    {
        $a = $this->createApp();
        $original = $a->getUpdatedAt();
        usleep(1000);
        $a->setProcedureId('proc-2');
        $this->assertSame('proc-2', $a->getProcedureId());
        $this->assertNotEquals($original, $a->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $a = $this->createApp();
        $a->setStatus('APPROVED');
        $this->assertTrue($a->isApproved());
        $this->assertFalse($a->isPending());

        $a->setStatus('REJECTED');
        $this->assertTrue($a->isRejected());
        $a->setStatus('PROCESSING');
        $this->assertTrue($a->isProcessing());
        $a->setStatus('COMPLETED');
        $this->assertTrue($a->isCompleted());
        $a->setStatus('CANCELLED');
        $this->assertTrue($a->isCancelled());
    }

    public function testSetData(): void
    {
        $a = $this->createApp();
        $original = $a->getUpdatedAt();
        usleep(1000);
        $data = ['forms' => ['doc1' => 'verified']];
        $a->setData($data);
        $this->assertSame($data, $a->getData());
        $this->assertNotEquals($original, $a->getUpdatedAt());
        $a->setData(null);
        $this->assertNull($a->getData());
    }
}
