<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\WorkflowInstance;
use DateTime;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowInstanceTest extends TestCase
{
    private function createInstance(
        string $id = 'instance-id',
        string $workflowId = 'wf-1'
    ): WorkflowInstance {
        return new WorkflowInstance($id, $workflowId);
    }

    public function testConstructor(): void
    {
        $i = $this->createInstance();
        $this->assertSame('instance-id', $i->getId());
        $this->assertSame('wf-1', $i->getWorkflowId());
        $this->assertNull($i->getUserId());
        $this->assertSame('PENDING', $i->getStatus());
        $this->assertNull($i->getState());
        $this->assertNull($i->getContext());
        $this->assertInstanceOf(DateTime::class, $i->getStartedAt());
        $this->assertNull($i->getCompletedAt());
        $this->assertInstanceOf(DateTime::class, $i->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $i->getUpdatedAt());
        $this->assertTrue($i->isPending());
        $this->assertFalse($i->isRunning());
        $this->assertFalse($i->isCompleted());
        $this->assertFalse($i->isFailed());
        $this->assertFalse($i->isCancelled());
    }

    public function testSetUserId(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $i->setUserId('user-2');
        $this->assertSame('user-2', $i->getUserId());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
    }

    public function testSetStatus(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $i->setStatus('RUNNING');
        $this->assertSame('RUNNING', $i->getStatus());
        $this->assertTrue($i->isRunning());
        $this->assertFalse($i->isPending());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
    }

    public function testSetState(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $state = ['step' => 1];
        $i->setState($state);
        $this->assertSame($state, $i->getState());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
        $i->setState(null);
        $this->assertNull($i->getState());
    }

    public function testSetContext(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $ctx = ['input' => 'data'];
        $i->setContext($ctx);
        $this->assertSame($ctx, $i->getContext());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
        $i->setContext(null);
        $this->assertNull($i->getContext());
    }

    public function testSetCompletedAt(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $completed = new DateTime();
        $i->setCompletedAt($completed);
        $this->assertSame($completed, $i->getCompletedAt());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
        $i->setCompletedAt(null);
        $this->assertNull($i->getCompletedAt());
    }

    public function testComplete(): void
    {
        $i = $this->createInstance();
        $originalUpdatedAt = $i->getUpdatedAt();
        usleep(1000);
        $result = $i->complete();
        $this->assertSame($i, $result); // fluent
        $this->assertSame('COMPLETED', $i->getStatus());
        $this->assertTrue($i->isCompleted());
        $this->assertInstanceOf(DateTime::class, $i->getCompletedAt());
        $this->assertNotEquals($originalUpdatedAt, $i->getUpdatedAt());
    }

    public function testFail(): void
    {
        $i = $this->createInstance();
        $i->fail();
        $this->assertSame('FAILED', $i->getStatus());
        $this->assertTrue($i->isFailed());
        $this->assertInstanceOf(DateTime::class, $i->getCompletedAt());
    }

    public function testCancel(): void
    {
        $i = $this->createInstance();
        $i->cancel();
        $this->assertSame('CANCELLED', $i->getStatus());
        $this->assertTrue($i->isCancelled());
        $this->assertInstanceOf(DateTime::class, $i->getCompletedAt());
    }
}
