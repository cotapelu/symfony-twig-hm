<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\WorkflowInstanceRepository;
use App\Entity\Common\WorkflowInstance;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowInstanceRepositoryTest extends TestCase
{
    private WorkflowInstanceRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(WorkflowInstanceRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByWorkflowId(): void
    {
        $workflowId = 'wf-123';
        $expected = [$this->createMock(WorkflowInstance::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['workflowId' => $workflowId], ['startedAt' => 'DESC'])
            ->willReturn($expected);

        $result = $this->repo->findByWorkflowId($workflowId);
        $this->assertSame($expected, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = 'user-123';
        $expected = [$this->createMock(WorkflowInstance::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['startedAt' => 'DESC'])
            ->willReturn($expected);

        $result = $this->repo->findByUserId($userId);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'RUNNING';
        $expected = [$this->createMock(WorkflowInstance::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status], ['startedAt' => 'DESC'])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindRunning(): void
    {
        $expected = [$this->createMock(WorkflowInstance::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'RUNNING'])
            ->willReturn($expected);

        $result = $this->repo->findRunning();
        $this->assertSame($expected, $result);
    }

    public function testFindByWorkflowAndStatus(): void
    {
        $workflowId = 'wf-123';
        $status = 'COMPLETED';
        $expected = [$this->createMock(WorkflowInstance::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['workflowId' => $workflowId, 'status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByWorkflowAndStatus($workflowId, $status);
        $this->assertSame($expected, $result);
    }
}
