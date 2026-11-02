<?php declare(strict_types=1);

namespace App\Tests\Repository\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Common\WorkflowRepository;
use App\Entity\Common\Workflow;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WorkflowRepositoryTest extends TestCase
{
    private WorkflowRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(WorkflowRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByType(): void
    {
        $type = 'APPROVAL';
        $expected = [$this->createMock(Workflow::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($expected);

        $result = $this->repo->findByType($type);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveWorkflows(): void
    {
        $expected = [$this->createMock(Workflow::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveWorkflows();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveWorkflows(): void
    {
        $expected = [$this->createMock(Workflow::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveWorkflows();
        $this->assertSame($expected, $result);
    }

    public function testFindDraftWorkflows(): void
    {
        $expected = [$this->createMock(Workflow::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'DRAFT'])
            ->willReturn($expected);

        $result = $this->repo->findDraftWorkflows();
        $this->assertSame($expected, $result);
    }

    public function testCountActive(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'ACTIVE'])
            ->willReturn(7);

        $result = $this->repo->countActive();
        $this->assertSame(7, $result);
    }

    public function testCountInactive(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'INACTIVE'])
            ->willReturn(3);

        $result = $this->repo->countInactive();
        $this->assertSame(3, $result);
    }

    public function testCountDraft(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'DRAFT'])
            ->willReturn(2);

        $result = $this->repo->countDraft();
        $this->assertSame(2, $result);
    }
}
