<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\ValueObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\ValueObject\WorkflowId;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowIdTest extends TestCase
{
    public function testConstructorGeneratesUuidIfNull(): void
    {
        $id = new WorkflowId();
        $this->assertNotNull($id->toString());
        $this->assertSame($id->toString(), (string)$id);
    }

    public function testConstructorAcceptsGivenId(): void
    {
        $id = new WorkflowId('wf-456');
        $this->assertSame('wf-456', $id->toString());
        $this->assertSame('wf-456', (string)$id);
    }

    public function testEquals(): void
    {
        $id1 = new WorkflowId('id1');
        $id2 = new WorkflowId('id1');
        $id3 = new WorkflowId('id2');
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
